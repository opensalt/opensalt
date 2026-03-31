<?php

declare(strict_types=1);

namespace App\VectorSearch\Store;

use App\VectorSearch\Entity\LsItemEmbedding;

interface VectorStoreInterface
{
    /**
     * @param list<float> $vector
     */
    public function storeEmbeddingVector(LsItemEmbedding $embedding, array $vector): void;

    /**
     * Persist any backend-specific work that should happen only after Doctrine flush.
     *
     * @param list<float> $vector
     */
    public function finalizeStoredEmbedding(LsItemEmbedding $embedding, array $vector): void;

    /**
     * @param list<array{
     *   lsItemId: int,
     *   frameworkId: int,
     *   kind: int,
     *   text: string,
     *   isLeafNode: bool,
     *   sourceHierarchyUpdatedAt: \DateTimeImmutable,
     *   vector: list<float>
     * }> $rows
     */
    public function storeEmbeddingBatch(array $rows): int;

    /**
     * @param list<float> $queryVector
     * @return list<array{lsItemId: int, similarity: float}>
     */
    public function search(
        array $queryVector,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array;

    /**
     * @return list<float>|null
     */
    public function getVectorByLsItemId(int $lsItemId): ?array;

    public function deleteByLsItemId(int $lsItemId): void;

    public function getVectorCount(): int;
}
