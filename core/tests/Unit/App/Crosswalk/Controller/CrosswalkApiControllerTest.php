<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Controller;

use App\Crosswalk\Controller\CrosswalkApiController;
use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Entity\Framework\LsDoc;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;

final class CrosswalkApiControllerTest extends TestCase
{
    public function testEstimateReturnsCounts(): void
    {
        $lsDocOg = $this->createMock(LsDoc::class);
        $lsDocOg->method('getId')->willReturn(42);

        $lsDocDest = $this->createMock(LsDoc::class);
        $lsDocDest->method('getId')->willReturn(87);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($class) use ($lsDocOg, $lsDocDest) {
            $repo = $this->createMock($class === LsDoc::class ? \App\Repository\Framework\LsDocRepository::class : \Doctrine\ORM\EntityRepository::class);
            if ($class === LsDoc::class) {
                $repo->method('find')->willReturnMap([
                    [42, null, null, $lsDocOg],
                    [87, null, null, $lsDocDest],
                ]);
            }
            return $repo;
        });

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->method('getVectorCount')->willReturn(1832);

        $controller = new CrosswalkApiController($vectorSearchService, $em);

        $request = new Request(['origin' => '42', 'destination' => '87', 'threshold' => '0.75']);
        $response = $controller->estimate($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(42, $data['origin_framework_id']);
        $this->assertSame(87, $data['destination_framework_id']);
        $this->assertSame(0.75, $data['threshold']);
    }

    public function testEstimateReturns400ForMissingParams(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $vectorSearchService = $this->createMock(VectorSearchService::class);

        $controller = new CrosswalkApiController($vectorSearchService, $em);

        $request = new Request(['threshold' => '0.75']);
        $response = $controller->estimate($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateDispatchesMessage(): void
    {
        $lsDocOg = $this->createMock(LsDoc::class);
        $lsDocOg->method('getId')->willReturn(42);

        $lsDocDest = $this->createMock(LsDoc::class);
        $lsDocDest->method('getId')->willReturn(87);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($class) use ($lsDocOg, $lsDocDest) {
            $repo = $this->createMock(\App\Repository\Framework\LsDocRepository::class);
            $repo->method('find')->willReturnMap([
                [42, null, null, $lsDocOg],
                [87, null, null, $lsDocDest],
            ]);
            return $repo;
        });

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

        $controller = new CrosswalkApiController($vectorSearchService, $em, $jobRepo, $messageBus);

        $request = Request::create(
            '/api/crosswalk',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'origin_id' => 42,
                'destination_id' => 87,
                'crosswalk_id' => null,
                'threshold' => 0.75,
                'exact_match_threshold' => 0.90,
            ])
        );

        $response = $controller->create($request);
        $this->assertSame(202, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('queued', $data['status']);
        $this->assertArrayHasKey('job_id', $data);
    }

    public function testGetJobStatus(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(0.95, true);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new CrosswalkApiController($vectorSearchService, $em, $jobRepo);
        $response = $controller->getJobStatus('test-job-id');

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('running', $data['status']);
        $this->assertSame(1, $data['progress']['processed']);
        $this->assertSame(100, $data['progress']['total']);
        $this->assertSame(1, $data['progress']['exact_match_items']);
    }

    public function testCancel(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new CrosswalkApiController($vectorSearchService, $em, $jobRepo);
        $response = $controller->cancel('test-job-id');

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('cancelled', $data['status']);
    }
}
