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
     * Perform backend-native full-text keyword search.
     *
     * @param string $queryText The raw text query for keyword search
     * @param int $limit Maximum number of results to return
     * @param int|null $frameworkId Optional framework filter
     * @param bool $leafOnly Whether to restrict results to leaf nodes
     * @param int|null $kind Optional item kind filter
     *
     * @return list<array{lsItemId: int, similarity: float}>
     */
    public function searchFullText(
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array;

    /**
     * Perform hybrid search combining dense vector similarity with BM25 full-text search.
     *
     * @param list<float> $queryVector The dense embedding vector for semantic search
     * @param string $queryText The raw text query for BM25 keyword search
     * @param int $limit Maximum number of results to return
     * @param int|null $frameworkId Optional framework filter
     * @param bool $leafOnly Whether to restrict results to leaf nodes
     * @param int|null $kind Optional item kind filter
     * @param int $prefetchLimit Number of candidates to fetch per sub-query before fusion
     *
     * @return list<array{lsItemId: int, similarity: float}> Results sorted by fused score descending
     */
    public function searchHybrid(
        array $queryVector,
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
        int $prefetchLimit = 20,
    ): array;

    /**
     * @return list<float>|null
     */
    public function getVectorByLsItemId(int $lsItemId): ?array;

    public function deleteByLsItemId(int $lsItemId): void;

    public function getVectorCount(): int;
}
