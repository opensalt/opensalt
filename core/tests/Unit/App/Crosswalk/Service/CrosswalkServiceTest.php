<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Service;

use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->createMock(\Doctrine\DBAL\Connection::class));
        $entityManager->method('getReference')
            ->with(LsDoc::class, 156)
            ->willReturn($crosswalkDoc);

        $service = new CrosswalkService($vectorSearchService, $entityManager);

        $result = $service->processItem(
            $sourceItem,
            $destItem,
            similarity: 0.95,
            exactMatchThreshold: 0.90,
            crosswalkDocId: 156,
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

        $vectorSearchService = $this->createMock(VectorSearchService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->createMock(\Doctrine\DBAL\Connection::class));
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
        );

        $this->assertSame(CrosswalkService::RESULT_SKIPPED_BELOW_THRESHOLD, $result);
    }
}
