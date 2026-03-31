<?php

declare(strict_types=1);

namespace Tests\Unit\App\VectorSearch\Service;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Repository\LsItemEmbeddingRepository;
use App\VectorSearch\Service\EmbeddingService;
use App\VectorSearch\Service\VectorSearchService;
use App\VectorSearch\Store\VectorStoreInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class VectorSearchServiceTest extends TestCase
{
    public function testSearchByKeywordUsesVectorStoreFullTextResults(): void
    {
        $embeddingService = $this->createMock(EmbeddingService::class);
        $vectorStore = $this->createMock(VectorStoreInterface::class);
        $embeddingRepository = $this->createMock(LsItemEmbeddingRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $lsDoc = new LsDoc();
        $lsItem = $lsDoc->createItem('2c7a6f13-9365-4f0c-a563-6f0d4da54d4d');
        $lsItem->setFullStatement('Solve multistep equations');

        $embedding = new LsItemEmbedding($lsItem, 'Solve multistep equations');

        $vectorStore->expects(self::once())
            ->method('searchFullText')
            ->with('equations', 15, 22, true, 3)
            ->willReturn([
                [
                    'lsItemId' => 101,
                    'similarity' => 0.84,
                ],
            ]);

        $embeddingRepository->expects(self::once())
            ->method('findByLsItemIdsIndexed')
            ->with([101])
            ->willReturn([
                101 => $embedding,
            ]);

        $service = new VectorSearchService(
            $embeddingService,
            $vectorStore,
            $embeddingRepository,
            $entityManager,
            $logger,
            'qdrant',
        );

        $results = $service->searchByKeyword('equations', 15, 22, true, 3);

        self::assertCount(1, $results);
        self::assertSame($lsItem, $results[0]['lsItem']);
        self::assertSame($embedding, $results[0]['embedding']);
        self::assertSame(0.84, $results[0]['similarity']);
    }
}
