<?php

declare(strict_types=1);

namespace Tests\Unit\App\VectorSearch\Service;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use App\VectorSearch\Store\HybridQdrantStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\Test\InMemoryPlatform;

final class VectorSearchServiceTest extends TestCase
{
    public function testSearchByKeywordResolvesLsItemsFromVectorResults(): void
    {
        $platform = new InMemoryPlatform('unused');
        $qdrantStore = $this->createMock(HybridQdrantStore::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $lsDoc = new LsDoc();
        $lsItem = $lsDoc->createItem('2c7a6f13-9365-4f0c-a563-6f0d4da54d4d');
        $lsItem->setFullStatement('Solve multistep equations');
        $this->setLsItemId($lsItem, 101);

        $qdrantStore->expects(self::once())
            ->method('searchFullText')
            ->with('equations', 15, 22, true, 3)
            ->willReturn([
                [
                    'lsItemId' => 101,
                    'similarity' => 0.84,
                ],
            ]);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [101]])
            ->willReturn([$lsItem]);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(LsItem::class)
            ->willReturn($repository);

        $service = new VectorSearchService(
            $platform,
            $qdrantStore,
            $entityManager,
            $logger,
        );

        $results = $service->searchByKeyword('equations', 15, 22, true, 3);

        self::assertCount(1, $results);
        self::assertSame($lsItem, $results[0]['lsItem']);
        self::assertSame(0.84, $results[0]['similarity']);
    }

    public function testHasEmbeddingReturnsTrueWhenPointExists(): void
    {
        $platform = new InMemoryPlatform('unused');
        $qdrantStore = $this->createMock(HybridQdrantStore::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $lsDoc = new LsDoc();
        $lsItem = $lsDoc->createItem('3d8b7a24-0476-5a1d-b674-7a1e5eb65e5e');
        $this->setLsItemId($lsItem, 200);

        $qdrantStore->expects(self::once())
            ->method('pointExists')
            ->with(200)
            ->willReturn(true);

        $service = new VectorSearchService(
            $platform,
            $qdrantStore,
            $entityManager,
            $logger,
        );

        self::assertTrue($service->hasEmbedding($lsItem));
    }

    public function testDeleteEmbeddingDelegatesToQdrantStore(): void
    {
        $platform = new InMemoryPlatform('unused');
        $qdrantStore = $this->createMock(HybridQdrantStore::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $lsDoc = new LsDoc();
        $lsItem = $lsDoc->createItem('4e9c8a35-1587-6a2e-c785-8a2f6fc76f6f');
        $this->setLsItemId($lsItem, 300);

        $qdrantStore->expects(self::once())
            ->method('deleteByLsItemId')
            ->with(300);

        $service = new VectorSearchService(
            $platform,
            $qdrantStore,
            $entityManager,
            $logger,
        );

        $service->deleteEmbedding($lsItem);
    }

    private function setLsItemId(LsItem $lsItem, int $id): void
    {
        $reflection = new \ReflectionProperty($lsItem, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($lsItem, $id);
    }
}
