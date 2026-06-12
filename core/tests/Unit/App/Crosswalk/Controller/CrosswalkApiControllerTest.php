<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Controller;

use App\Crosswalk\Controller\CrosswalkApiController;
use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CrosswalkApiControllerTest extends TestCase
{
    private function createController(
        VectorSearchService $vectorSearchService,
        EntityManagerInterface $em,
        ?CrosswalkJobRepository $jobRepository = null,
        ?MessageBusInterface $messageBus = null,
    ): CrosswalkApiController {
        $controller = $this->getMockBuilder(CrosswalkApiController::class)
            ->setConstructorArgs([$vectorSearchService, $em, $jobRepository, $messageBus])
            ->onlyMethods(['isGranted', 'denyAccessUnlessGranted'])
            ->getMock();

        $controller->method('isGranted')->willReturn(true);
        $controller->method('denyAccessUnlessGranted');

        return $controller;
    }

    private function createEmWithDocRepos(array $docs, ?EntityRepository $lsItemRepo = null): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($class) use ($docs, $lsItemRepo) {
            if ($class === LsItem::class) {
                return $lsItemRepo ?? $this->createMock(EntityRepository::class);
            }

            $repo = $this->createMock(LsDocRepository::class);
            $repo->method('findOneBy')->willReturnCallback(function ($criteria) use ($docs) {
                $identifier = $criteria['identifier'] ?? null;

                return $docs[$identifier] ?? null;
            });
            $repo->method('find')->willReturnCallback(function ($id) use ($docs) {
                foreach ($docs as $doc) {
                    if ($doc->getId() == $id) {
                        return $doc;
                    }
                }

                return null;
            });

            return $repo;
        });

        return $em;
    }

    public function testEstimateReturnsCounts(): void
    {
        $lsDocOg = $this->createMock(LsDoc::class);
        $lsDocOg->method('getId')->willReturn(42);
        $lsDocOg->method('getIdentifier')->willReturn('uuid-origin-42');

        $lsDocDest = $this->createMock(LsDoc::class);
        $lsDocDest->method('getId')->willReturn(87);
        $lsDocDest->method('getIdentifier')->willReturn('uuid-dest-87');

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->method('getVectorCountForFramework')->willReturnMap([
            [42, 150],
            [87, 200],
        ]);

        $lsItemRepo = $this->createMock(EntityRepository::class);
        $lsItemRepo->method('count')->with(['lsDoc' => 42])->willReturn(180);

        $em = $this->createEmWithDocRepos(
            ['uuid-origin-42' => $lsDocOg, 'uuid-dest-87' => $lsDocDest],
            $lsItemRepo,
        );

        $controller = $this->createController($vectorSearchService, $em);

        $request = new Request(['origin' => 'uuid-origin-42', 'destination' => 'uuid-dest-87', 'threshold' => '0.75']);
        $response = $controller->estimate($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(42, $data['origin_framework_id']);
        $this->assertSame(87, $data['destination_framework_id']);
        $this->assertSame('uuid-origin-42', $data['origin_framework_identifier']);
        $this->assertSame('uuid-dest-87', $data['destination_framework_identifier']);
        $this->assertSame(0.75, $data['threshold']);
        $this->assertSame(150, $data['origin_items_with_embeddings']);
        $this->assertSame(30, $data['origin_items_without_embeddings']);
        $this->assertSame(200, $data['destination_items_with_embeddings']);
    }

    public function testEstimateReturns400ForMissingParams(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $vectorSearchService = $this->createMock(VectorSearchService::class);

        $controller = $this->createController($vectorSearchService, $em);

        $request = new Request(['threshold' => '0.75']);
        $response = $controller->estimate($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEstimateReturns404ForUnknownIdentifier(): void
    {
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $em = $this->createEmWithDocRepos([]);

        $controller = $this->createController($vectorSearchService, $em);

        $request = new Request(['origin' => 'uuid-unknown', 'destination' => 'uuid-unknown2']);
        $response = $controller->estimate($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateDispatchesMessage(): void
    {
        $lsDocOg = $this->createMock(LsDoc::class);
        $lsDocOg->method('getId')->willReturn(42);

        $lsDocDest = $this->createMock(LsDoc::class);
        $lsDocDest->method('getId')->willReturn(87);

        $lsDocCrosswalk = $this->createMock(LsDoc::class);
        $lsDocCrosswalk->method('getId')->willReturn(156);

        $em = $this->createEmWithDocRepos([
            'uuid-origin-42' => $lsDocOg,
            'uuid-dest-87' => $lsDocDest,
            'uuid-crosswalk-156' => $lsDocCrosswalk,
        ]);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->expects($this->once())->method('save');

        $vectorSearchService = $this->createMock(VectorSearchService::class);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) {
                $this->assertInstanceOf(CreateCrosswalkMessage::class, $message);

                return new Envelope($message);
            });

        $controller = $this->createController($vectorSearchService, $em, $jobRepo, $messageBus);

        $request = Request::create(
            '/api/crosswalk',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'origin_identifier' => 'uuid-origin-42',
                'destination_identifier' => 'uuid-dest-87',
                'crosswalk_identifier' => 'uuid-crosswalk-156',
                'threshold' => 0.75,
                'exact_match_threshold' => 0.90,
            ]),
        );

        $response = $controller->create($request);
        $this->assertSame(202, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('queued', $data['status']);
        $this->assertArrayHasKey('job_id', $data);
        // job_id is now a UUID string from Symfony\Component\Uid\Uuid
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $data['job_id'],
        );
    }

    public function testCreateReturns400ForMissingParams(): void
    {
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = $this->createController($vectorSearchService, $em);

        $request = Request::create(
            '/api/crosswalk',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['origin_identifier' => 'uuid-origin-42']),
        );

        $response = $controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateReturns404ForUnknownIdentifier(): void
    {
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $em = $this->createEmWithDocRepos([]);

        $controller = $this->createController($vectorSearchService, $em);

        $request = Request::create(
            '/api/crosswalk',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'origin_identifier' => 'uuid-unknown',
                'destination_identifier' => 'uuid-unknown2',
                'crosswalk_identifier' => 'uuid-unknown3',
            ]),
        );

        $response = $controller->create($request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetJobStatus(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(0.95, true);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $vectorSearchService = $this->createMock(VectorSearchService::class);

        $crosswalkDoc = $this->createMock(LsDoc::class);
        $crosswalkDoc->method('getId')->willReturn(156);

        $em = $this->createEmWithDocRepos(['uuid-crosswalk-156' => $crosswalkDoc]);

        $controller = $this->createController($vectorSearchService, $em, $jobRepo);
        $response = $controller->getJobStatus((string) $job->id);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('running', $data['status']);
        $this->assertSame(1, $data['progress']['processed']);
        $this->assertSame(100, $data['progress']['total']);
        $this->assertSame(1, $data['progress']['exact_match_items']);
        $this->assertArrayHasKey('skipped_no_embedding', $data['progress']);
        $this->assertArrayHasKey('skipped_below_threshold', $data['progress']);
    }

    public function testCancel(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $vectorSearchService = $this->createMock(VectorSearchService::class);

        $crosswalkDoc = $this->createMock(LsDoc::class);
        $crosswalkDoc->method('getId')->willReturn(156);

        $em = $this->createEmWithDocRepos(['uuid-crosswalk-156' => $crosswalkDoc]);

        $controller = $this->createController($vectorSearchService, $em, $jobRepo);
        $response = $controller->cancel((string) $job->id);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('cancelled', $data['status']);
    }
}
