<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\MessageHandler\CreateCrosswalkMessageHandler;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CreateCrosswalkMessageHandlerTest extends TestCase
{
    public function testHandlerProcessesItemsAndCreatesAssociations(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: 'test-job-id',
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
            threshold: 0.75,
            exactMatchThreshold: 0.90,
        );

        $job = $this->createMock(CrosswalkJob::class);
        $job->method('getId')->willReturn('test-job-id');
        $job->expects($this->once())->method('markStarted');
        $job->expects($this->once())->method('markCompleted');

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);

        $destItem = $this->createMock(LsItem::class);
        $destItem->method('getId')->willReturn(200);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->method('searchByLsItem')
            ->willReturn([['lsItem' => $destItem, 'similarity' => 0.95]]);

        $crosswalkService = $this->createMock(CrosswalkService::class);
        $crosswalkService->method('processItem')
            ->willReturn(CrosswalkService::RESULT_CREATED_EXACT);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($class) use ($sourceItem) {
            $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
            if ($class === LsItem::class) {
                $repo->method('findBy')->willReturn([$sourceItem]);
            }
            return $repo;
        });

        $hub = $this->createMock(\Symfony\Component\Mercure\HubInterface::class);

        $handler = new CreateCrosswalkMessageHandler(
            $jobRepo, $vectorSearchService, $crosswalkService, $em, $hub,
        );

        $handler->__invoke($message);
    }
}
