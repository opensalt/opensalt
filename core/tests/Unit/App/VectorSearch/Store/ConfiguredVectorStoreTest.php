<?php

declare(strict_types=1);

namespace Tests\Unit\App\VectorSearch\Store;

use App\VectorSearch\Service\VectorTableService;
use App\VectorSearch\Store\ConfiguredVectorStore;
use App\VectorSearch\Store\QdrantVectorStore;
use PHPUnit\Framework\TestCase;

final class ConfiguredVectorStoreTest extends TestCase
{
    public function testSearchFullTextDelegatesToQdrantBackend(): void
    {
        $mySqlVectorStore = $this->createMock(VectorTableService::class);
        $qdrantVectorStore = $this->createMock(QdrantVectorStore::class);

        $mySqlVectorStore->expects(self::never())
            ->method('searchFullText');
        $qdrantVectorStore->expects(self::once())
            ->method('searchFullText')
            ->with('fractions', 12, 7, true, 4)
            ->willReturn([
                [
                    'lsItemId' => 200,
                    'similarity' => 0.91,
                ],
            ]);

        $store = new ConfiguredVectorStore($mySqlVectorStore, $qdrantVectorStore, 'qdrant');

        self::assertSame(
            [
                [
                    'lsItemId' => 200,
                    'similarity' => 0.91,
                ],
            ],
            $store->searchFullText('fractions', 12, 7, true, 4)
        );
    }
}
