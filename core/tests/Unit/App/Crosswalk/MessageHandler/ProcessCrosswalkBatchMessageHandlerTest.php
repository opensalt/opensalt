<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\ProcessCrosswalkBatchMessage;
use App\Crosswalk\MessageHandler\ProcessCrosswalkBatchMessageHandler;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Ramsey\Uuid\Uuid;

final class ProcessCrosswalkBatchMessageHandlerTest extends TestCase
{
    private function createJob(int $totalItems = 2): CrosswalkJob
    {
        $job = new CrosswalkJob(42, 87, 156);
        $jobRef = new \ReflectionProperty($job, 'id');
        $jobRef->setValue($job, Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'));

        $job->markStarted($totalItems);

        return $job;
    }

    public function testProcessesBatchAndMarksCompletedWhenAllDone(): void
    {
        $job = $this->createJob(2);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $sourceItem1 = $this->createMock(LsItem::class);
        $sourceItem1->method('getId')->willReturn(100);
        $sourceItem2 = $this->createMock(LsItem::class);
        $sourceItem2->method('getId')->willReturn(101);

        $destItem = $this->createMock(LsItem::class);

        $crosswalkService = $this->createMock(CrosswalkService::class);
        $crosswalkService->method('findBestMatch')
            ->willReturn(['lsItem' => $destItem, 'similarity' => 0.95]);
        $crosswalkService->method('processItem')
            ->willReturn(CrosswalkService::RESULT_CREATED_EXACT);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('running');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturnCallback(function () use ($sourceItem1, $sourceItem2) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn([$sourceItem1, $sourceItem2]);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: [100, 101],
        ));

        $this->assertSame('completed', $job->status);
        $this->assertSame(2, $job->processedItems);
        $this->assertSame(2, $job->exactMatchItems);
    }

    public function testDoesNotMarkCompletedWhenMoreItemsRemain(): void
    {
        $job = $this->createJob(10);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);

        $destItem = $this->createMock(LsItem::class);

        $crosswalkService = $this->createMock(CrosswalkService::class);
        $crosswalkService->method('findBestMatch')
            ->willReturn(['lsItem' => $destItem, 'similarity' => 0.95]);
        $crosswalkService->method('processItem')
            ->willReturn(CrosswalkService::RESULT_CREATED_EXACT);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('running');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturnCallback(function () use ($sourceItem) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn([$sourceItem]);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: [100],
        ));

        $this->assertSame('running', $job->status);
        $this->assertSame(1, $job->processedItems);
    }

    public function testReturnsEarlyIfJobAlreadyCancelled(): void
    {
        $job = $this->createJob(2);
        $job->markCancelled();

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $em = $this->createMock(EntityManagerInterface::class);
        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo,
            $this->createMock(CrosswalkService::class),
            $em,
            $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: [100, 101],
        ));

        $this->assertSame('cancelled', $job->status);
        $this->assertSame(0, $job->processedItems);
    }

    public function testStopsProcessingWhenCancelledMidBatch(): void
    {
        $job = $this->createJob(15);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $items = [];
        for ($i = 0; $i < 15; ++$i) {
            $item = $this->createMock(LsItem::class);
            $item->method('getId')->willReturn(100 + $i);
            $items[] = $item;
        }

        $destItem = $this->createMock(LsItem::class);

        $crosswalkService = $this->createMock(CrosswalkService::class);
        $crosswalkService->method('findBestMatch')
            ->willReturn(['lsItem' => $destItem, 'similarity' => 0.95]);
        $crosswalkService->method('processItem')
            ->willReturn(CrosswalkService::RESULT_CREATED_EXACT);

        // The first fetchOne call happens at the 5-item flush checkpoint inside the loop.
        // Return 'cancelled' so processing stops after 5 items.
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('cancelled');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturnCallback(function () use ($items) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn($items);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: array_map(fn (LsItem $i): int => (int) $i->getId(), $items),
        ));

        // The flush at item 5 should have detected cancellation and stopped.
        $this->assertSame('running', $job->status);
        $this->assertSame(5, $job->processedItems);
    }

    public function testReturnsEarlyIfJobNotFound(): void
    {
        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo,
            $this->createMock(CrosswalkService::class),
            $em,
            $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: 'nonexistent',
            itemIds: [100],
        ));

        $this->assertTrue(true);
    }

    public function testCountsDuplicateSkipsAsMatched(): void
    {
        $job = $this->createJob(1);

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);

        $destItem = $this->createMock(LsItem::class);

        $crosswalkService = $this->createMock(CrosswalkService::class);
        $crosswalkService->method('findBestMatch')
            ->willReturn(['lsItem' => $destItem, 'similarity' => 0.95]);
        $crosswalkService->method('processItem')
            ->willReturn(CrosswalkService::RESULT_SKIPPED_DUPLICATE);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('running');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getRepository')->willReturnCallback(function () use ($sourceItem) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn([$sourceItem]);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $handler = new ProcessCrosswalkBatchMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub,
        );

        $handler->__invoke(new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: [100],
        ));

        // Duplicate items are counted as matched (association already exists).
        $this->assertSame('completed', $job->status);
        $this->assertSame(1, $job->processedItems);
        $this->assertSame(1, $job->matchedItems);
        $this->assertSame(1, $job->exactMatchItems);
    }
}
