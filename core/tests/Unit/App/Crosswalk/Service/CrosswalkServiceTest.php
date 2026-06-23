<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Service;

use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CrosswalkServiceTest extends TestCase
{
    public function testProcessItemCreatesExactMatchForHighConfidence(): void
    {
        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);
        $sourceItem->method('getLsDoc')->willReturn($this->createMock(LsDoc::class));

        $destLsDoc = $this->createMock(LsDoc::class);
        $destLsDoc->method('getId')->willReturn(87);

        $destItem = $this->createMock(LsItem::class);
        $destItem->method('getId')->willReturn(200);
        $destItem->method('getLsDoc')->willReturn($destLsDoc);
        $destItem->method('getIdentifier')->willReturn('dest-uuid');
        $destItem->method('getUri')->willReturn('https://example.com/item/200');
        $destItem->method('getFullStatement')->willReturn('Destination full statement');
        $destItem->method('getHumanCodingScheme')->willReturn('1.MA.2');

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->method('searchByLsItem')
            ->with($sourceItem, 1, 87)
            ->willReturn([['lsItem' => $destItem, 'similarity' => 0.95]]);

        $crosswalkDoc = $this->createMock(LsDoc::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getReference')
            ->with(LsDoc::class, 156)
            ->willReturn($crosswalkDoc);
        $entityManager->expects($this->once())
            ->method('persist');

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $result = $service->processItem(
            $sourceItem,
            $destItem,
            similarity: 0.95,
            exactMatchThreshold: 0.90,
            crosswalkDocId: 156,
            threshold: 0.75,
            jobId: 'test-job-id',
        );

        $this->assertSame(CrosswalkService::RESULT_CREATED_EXACT, $result);
    }

    public function testProcessItemCreatesIsRelatedForStandardConfidence(): void
    {
        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);
        $sourceItem->method('getLsDoc')->willReturn($this->createMock(LsDoc::class));

        $destLsDoc = $this->createMock(LsDoc::class);
        $destLsDoc->method('getId')->willReturn(87);

        $destItem = $this->createMock(LsItem::class);
        $destItem->method('getId')->willReturn(200);
        $destItem->method('getLsDoc')->willReturn($destLsDoc);
        $destItem->method('getIdentifier')->willReturn('dest-uuid');
        $destItem->method('getUri')->willReturn('https://example.com/item/200');
        $destItem->method('getFullStatement')->willReturn('Destination full statement');
        $destItem->method('getHumanCodingScheme')->willReturn('1.MA.2');

        $crosswalkDoc = $this->createMock(LsDoc::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getReference')
            ->with(LsDoc::class, 156)
            ->willReturn($crosswalkDoc);

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $result = $service->processItem(
            $sourceItem,
            $destItem,
            similarity: 0.80,
            exactMatchThreshold: 0.90,
            crosswalkDocId: 156,
            threshold: 0.75,
            jobId: 'test-job-id',
        );

        $this->assertSame(CrosswalkService::RESULT_CREATED_RELATED, $result);
    }

    public function testProcessItemSkipsBelowThreshold(): void
    {
        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CrosswalkService($vectorSearchService, $entityManager);
        $result = $service->processItem(
            $this->createMock(LsItem::class),
            $this->createMock(LsItem::class),
            similarity: 0.50,
            exactMatchThreshold: 0.90,
            crosswalkDocId: 156,
            threshold: 0.75,
            jobId: 'test-job-id',
        );

        $this->assertSame(CrosswalkService::RESULT_SKIPPED_BELOW_THRESHOLD, $result);
    }

    public function testProcessItemSkipsDuplicateAssociation(): void
    {
        $sourceItem = $this->createMock(LsItem::class);
        $sourceItem->method('getId')->willReturn(100);
        $sourceItem->method('getLsDoc')->willReturn($this->createMock(LsDoc::class));

        $destItem = $this->createMock(LsItem::class);
        $destItem->method('getId')->willReturn(200);
        $destItem->method('getLsDoc')->willReturn($this->createMock(LsDoc::class));
        $destItem->method('getIdentifier')->willReturn('dest-uuid');
        $destItem->method('getUri')->willReturn('https://example.com/item/200');
        $destItem->method('getFullStatement')->willReturn('Destination full statement');
        $destItem->method('getHumanCodingScheme')->willReturn('1.MA.2');

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('1');

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('getReference');

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $result = $service->processItem(
            $sourceItem,
            $destItem,
            similarity: 0.95,
            exactMatchThreshold: 0.90,
            crosswalkDocId: 156,
            threshold: 0.75,
            jobId: 'test-job-id',
        );

        $this->assertSame(CrosswalkService::RESULT_SKIPPED_DUPLICATE, $result);
    }

    public function testFindBestMatchPassesLeafOnlyThroughToVectorSearch(): void
    {
        $sourceItem = $this->createMock(LsItem::class);

        $destItem = $this->createMock(LsItem::class);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->expects($this->once())
            ->method('searchByLsItem')
            ->with($sourceItem, 1, 87, true)
            ->willReturn([['lsItem' => $destItem, 'similarity' => 0.85]]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $match = $service->findBestMatch(
            $sourceItem,
            destinationFrameworkId: 87,
            leafOnly: true,
        );

        $this->assertNotNull($match);
        $this->assertSame($destItem, $match['lsItem']);
        $this->assertSame(0.85, $match['similarity']);
    }

    public function testFindBestMatchReturnsBelowThresholdMatch(): void
    {
        $sourceItem = $this->createMock(LsItem::class);
        $destItem = $this->createMock(LsItem::class);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $vectorSearchService->expects($this->once())
            ->method('searchByLsItem')
            ->with($sourceItem, 1, 87, false)
            ->willReturn([['lsItem' => $destItem, 'similarity' => 0.50]]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        // findBestMatch no longer filters by threshold; it returns the best
        // candidate regardless of score so the handler can categorize it.
        $match = $service->findBestMatch($sourceItem, destinationFrameworkId: 87);

        $this->assertNotNull($match);
        $this->assertSame($destItem, $match['lsItem']);
        $this->assertSame(0.50, $match['similarity']);
    }

    public function testGetLeafItemIdsExcludesItemsThatAreParents(): void
    {
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('fetchAllAssociative')
            ->willReturn([
                ['id' => '10'],
                ['id' => '12'],
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $this->assertSame([10, 12], $service->getLeafItemIds(42));
        $this->assertSame(2, $service->countLeafItems(42));
    }
}
