<?php

declare(strict_types=1);

namespace App\VectorSearch\Service;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Repository\LsItemEmbeddingRepository;
use App\VectorSearch\Store\VectorStoreInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for managing vector search operations.
 */
readonly class VectorSearchService
{
    private const EMBEDDING_TEXT_SEPARATOR = "\n\n";
    private const EMBEDDING_GENERATION_CURSOR_KEY = 'embedding_generation_cursor';
    private const VECTOR_COUNT_KEY = 'vector_count';

    public function __construct(
        private EmbeddingService $embeddingService,
        private VectorStoreInterface $vectorStore,
        private LsItemEmbeddingRepository $embeddingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        #[Autowire('%env(string:VECTOR_SEARCH_BACKEND)%')]
        private string $vectorBackend = 'mysql',
    ) {
    }

    /**
     * Generate and store embedding for an LsItem.
     *
     * @param LsItem $lsItem The item to generate embedding for
     * @param string|null $text Optional text to embed
     * @return LsItemEmbedding The created embedding entity
     */
    public function generateAndStoreEmbedding(LsItem $lsItem, ?string $text = null): LsItemEmbedding
    {
        $lsItem = $this->resolveManagedLsItem($lsItem);
        $existingEmbedding = $this->embeddingRepository->findOneBy(['lsItem' => $lsItem]);
        $wasCounted = $existingEmbedding?->hasVectorData() ?? false;
        $embeddingText = $text ?? $this->buildEmbeddingText($lsItem);
        $isLeafNode = $this->isLeafNode($lsItem);
        $sourceHierarchyUpdatedAt = $this->getSourceHierarchyUpdatedAt($lsItem);

        $vector = $this->embeddingService->generateEmbedding($embeddingText);
        $embedding = $this->upsertEmbeddingEntity(
            $lsItem,
            $embeddingText,
            $isLeafNode,
            $sourceHierarchyUpdatedAt,
            $existingEmbedding
        );

        $this->vectorStore->storeEmbeddingVector($embedding, $vector);
        $this->entityManager->flush();
        $this->vectorStore->finalizeStoredEmbedding($embedding, $vector);
        $this->entityManager->flush();
        if (!$wasCounted) {
            $this->incrementCachedVectorCount(1);
        }

        $this->logger->info('Embedding generated and stored', [
            'ls_item_id' => $lsItem->getId(),
            'embedding_id' => $embedding->getId(),
            'leaf_node' => $isLeafNode,
            'source_hierarchy_updated_at' => $sourceHierarchyUpdatedAt->format(\DateTimeInterface::ATOM),
        ]);

        return $embedding;
    }

    /**
     * Generate and store embeddings for a set of items inside a single framework.
     *
     * @param list<int> $lsItemIds
     * @return array{processed: int, skipped: int, missing: int}
     */
    public function generateAndStoreEmbeddingsForFramework(
        int $frameworkId,
        array $lsItemIds,
        bool $force = false,
        int $batchSize = 50,
    ): array {
        if ([] === $lsItemIds) {
            return [
                'processed' => 0,
                'skipped' => 0,
                'missing' => 0,
            ];
        }

        $frameworkGraph = $this->loadFrameworkGraph($frameworkId);
        $embeddingStatuses = $this->embeddingRepository->getEmbeddingStatusByLsItemIds($lsItemIds);
        $textCache = [];
        $updatedAtCache = [];

        $processed = 0;
        $skipped = 0;
        $missing = 0;
        $batch = [];

        foreach ($lsItemIds as $lsItemId) {
            $frameworkItem = $frameworkGraph[$lsItemId] ?? null;
            if (null === $frameworkItem) {
                ++$missing;
                continue;
            }

            $sourceHierarchyUpdatedAt = $this->getSourceHierarchyUpdatedAtFromGraph($lsItemId, $frameworkGraph, $updatedAtCache);
            $embeddingStatus = $embeddingStatuses[$lsItemId] ?? null;

            if (
                !$force
                && ($embeddingStatus['hasVectorData'] ?? false)
                && $this->isEmbeddingStatusCurrent($embeddingStatus, $sourceHierarchyUpdatedAt)
            ) {
                ++$skipped;
                continue;
            }

            $batch[] = [
                'lsItemId' => $lsItemId,
                'frameworkId' => $frameworkId,
                'kind' => $this->getFrameworkItemKind($lsItemId, $frameworkGraph),
                'text' => $this->buildEmbeddingTextFromGraph($lsItemId, $frameworkGraph, $textCache),
                'isLeafNode' => (bool) $frameworkItem['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $sourceHierarchyUpdatedAt,
            ];

            if (count($batch) >= max(1, $batchSize)) {
                $processed += $this->storeFrameworkEmbeddingBatch($batch);
                $batch = [];
            }
        }

        if ([] !== $batch) {
            $processed += $this->storeFrameworkEmbeddingBatch($batch);
        }

        $this->logger->info('Framework embedding generation completed', [
            'framework_id' => $frameworkId,
            'requested_count' => count($lsItemIds),
            'processed_count' => $processed,
            'skipped_count' => $skipped,
            'missing_count' => $missing,
            'batch_size' => $batchSize,
        ]);

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'missing' => $missing,
        ];
    }

    /**
     * @param list<int> $lsItemIds
     * @return list<array{
     *   lsItemId: int,
     *   frameworkId: int,
     *   kind: int,
     *   text: string,
     *   isLeafNode: bool,
     *   sourceHierarchyUpdatedAt: \DateTimeImmutable
     * }>
     */
    public function buildEmbeddingRowsForFramework(int $frameworkId, array $lsItemIds): array
    {
        if ([] === $lsItemIds) {
            return [];
        }

        $frameworkGraph = $this->loadFrameworkGraph($frameworkId);
        $textCache = [];
        $updatedAtCache = [];
        $rows = [];

        foreach ($lsItemIds as $lsItemId) {
            $frameworkItem = $frameworkGraph[$lsItemId] ?? null;
            if (null === $frameworkItem) {
                continue;
            }

            $rows[] = [
                'lsItemId' => $lsItemId,
                'frameworkId' => $frameworkId,
                'kind' => $this->getFrameworkItemKind($lsItemId, $frameworkGraph),
                'text' => $this->buildEmbeddingTextFromGraph($lsItemId, $frameworkGraph, $textCache),
                'isLeafNode' => (bool) $frameworkItem['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $this->getSourceHierarchyUpdatedAtFromGraph($lsItemId, $frameworkGraph, $updatedAtCache),
            ];
        }

        return $rows;
    }

    /**
     * Build embedding rows for ALL items in a framework.
     *
     * Loads the framework graph once and builds the full embedding text
     * (item fullStatement + all ancestor fullStatements) for every item.
     *
     * Unlike buildEmbeddingRowsForFramework() which accepts a filtered list of item IDs,
     * this method processes every item that belongs to the framework.
     *
     * @return list<array{
     *   lsItemId: int,
     *   frameworkId: int,
     *   kind: int,
     *   text: string,
     *   isLeafNode: bool,
     *   sourceHierarchyUpdatedAt: \DateTimeImmutable
     * }>
     */
    public function buildAllEmbeddingRowsForFramework(int $frameworkId): array
    {
        $frameworkGraph = $this->loadFrameworkGraph($frameworkId);
        $textCache = [];
        $updatedAtCache = [];
        $rows = [];

        foreach ($frameworkGraph as $lsItemId => $frameworkItem) {
            // Skip placeholder entries created for cross-framework parent references.
            // These have epoch updatedAt, empty fullStatement, and kind 0.
            if (0 === $frameworkItem['updatedAt']->getTimestamp()
                && '' === $frameworkItem['fullStatement']
                && 0 === $frameworkItem['kind']
            ) {
                continue;
            }

            $rows[] = [
                'lsItemId' => $lsItemId,
                'frameworkId' => $frameworkId,
                'kind' => $this->getFrameworkItemKind($lsItemId, $frameworkGraph),
                'text' => $this->buildEmbeddingTextFromGraph($lsItemId, $frameworkGraph, $textCache),
                'isLeafNode' => (bool) $frameworkItem['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $this->getSourceHierarchyUpdatedAtFromGraph($lsItemId, $frameworkGraph, $updatedAtCache),
            ];
        }

        return $rows;
    }

    /**
     * Search for similar LsItems by text query.
     *
     * Delegates to searchHybridByQuery() so that Qdrant backends automatically
     * benefit from BM25 + vector fusion while MySQL backends fall back to
     * vector-only search with a warning.
     *
     * @return list<array{lsItem: LsItem, similarity: float, embedding: LsItemEmbedding}>
     */
    public function searchByQuery(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        return $this->searchHybridByQuery($query, $limit, $frameworkId, $leafOnly, $kind);
    }

    /**
     * Search for LsItems by backend-native keyword/full-text search.
     *
     * @return list<array{lsItem: LsItem, similarity: float, embedding: LsItemEmbedding}>
     */
    public function searchByKeyword(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $vectorResults = $this->vectorStore->searchFullText($query, $limit, $frameworkId, $leafOnly, $kind);

        $results = $this->resolveResults($vectorResults);

        $this->logger->debug('Keyword search completed', [
            'limit' => $limit,
            'framework_id' => $frameworkId,
            'leaf_only' => $leafOnly,
            'kind' => $kind,
            'results_count' => count($results),
        ]);

        return $results;
    }

    /**
     * Search for similar LsItems by vector.
     *
     * @param array $vector The query vector (384 dimensions)
     * @return array Array of LsItem entities with similarity scores
     */
    public function searchByVector(
        array $vector,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $vectorResults = $this->vectorStore->search($vector, $limit, $frameworkId, $leafOnly, $kind);

        $results = $this->resolveResults($vectorResults);

        $this->logger->debug('Vector similarity search completed', [
            'limit' => $limit,
            'framework_id' => $frameworkId,
            'leaf_only' => $leafOnly,
            'kind' => $kind,
            'results_count' => count($results),
        ]);

        return $results;
    }

    /**
     * Search for similar LsItems using hybrid BM25 + vector similarity search.
     *
     * @param string $query The text query to search for
     * @param int $limit Maximum number of results to return
     * @param int|null $frameworkId Optional framework filter
     * @param bool $leafOnly Whether to restrict results to leaf nodes only
     * @param int|null $kind Optional item kind filter
     * @param int $prefetchLimit Number of candidates to fetch per sub-query before fusion
     *
     * @return list<array{lsItem: LsItem, similarity: float, embedding: LsItemEmbedding}>
     */
    public function searchHybridByQuery(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
        int $prefetchLimit = 20,
    ): array {
        $queryVector = $this->embeddingService->generateEmbedding($query);

        $vectorResults = $this->vectorStore->searchHybrid(
            $queryVector,
            $query,
            $limit,
            $frameworkId,
            $leafOnly,
            $kind,
            $prefetchLimit,
        );

        return $this->resolveResults($vectorResults);
    }

    /**
     * Search for items similar to an existing LsItem.
     *
     * @return array Array of LsItem entities with similarity scores
     */
    public function searchByLsItem(
        LsItem $lsItem,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $embedding = $this->getEmbedding($lsItem);
        $sourceId = $lsItem->getId();
        if (null === $embedding || null === $sourceId) {
            return [];
        }

        $queryVector = $this->vectorStore->getVectorByLsItemId($sourceId);
        if (null === $queryVector) {
            return [];
        }

        $results = $this->searchByVector($queryVector, $limit + 1, $frameworkId, $leafOnly, $kind);

        $results = array_values(array_filter(
            $results,
            static fn (array $result): bool => $result['lsItem']->getId() !== $sourceId
        ));

        return array_slice($results, 0, $limit);
    }

    /**
     * Delete embedding for an LsItem.
     *
     * @param LsItem $lsItem The item to delete embedding for
     * @return bool True if deleted, false otherwise
     */
    public function deleteEmbedding(LsItem $lsItem): bool
    {
        $lsItem = $this->resolveManagedLsItem($lsItem);
        $embedding = $this->embeddingRepository->findOneBy(['lsItem' => $lsItem]);

        if (null === $embedding) {
            return false;
        }

        $wasCounted = $embedding->hasVectorData();
        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            throw new \RuntimeException('Cannot delete a vector embedding for an LsItem without an ID.');
        }

        $this->vectorStore->deleteByLsItemId($lsItemId);
        $this->entityManager->remove($embedding);
        $this->entityManager->flush();
        if ($wasCounted) {
            $this->incrementCachedVectorCount(-1);
        }

        $this->logger->info('Embedding deleted', [
            'ls_item_id' => $lsItem->getId(),
            'embedding_id' => $embedding->getId(),
        ]);

        return true;
    }

    /**
     * Get embedding for an LsItem.
     *
     * @param LsItem $lsItem The item to get embedding for
     * @return LsItemEmbedding|null The embedding entity or null if not found
     */
    public function getEmbedding(LsItem $lsItem): ?LsItemEmbedding
    {
        $lsItem = $this->resolveManagedLsItem($lsItem);

        return $this->embeddingRepository->findOneBy(['lsItem' => $lsItem]);
    }

    /**
     * Check if an LsItem has an embedding.
     *
     * @param LsItem $lsItem The item to check
     * @return bool True if embedding exists, false otherwise
     */
    public function hasEmbedding(LsItem $lsItem): bool
    {
        return $this->getEmbedding($lsItem)?->hasVectorData() ?? false;
    }

    /**
     * Get vector count.
     *
     * @return int Number of vectors in the table
     */
    public function getVectorCount(): int
    {
        if ($this->isQdrantBackend()) {
            $count = $this->vectorStore->getVectorCount();
            $this->setStateInt($this->getVectorCountStateKey(), $count);

            return $count;
        }

        $cachedCount = $this->getStateInt($this->getVectorCountStateKey());
        if (null !== $cachedCount) {
            return $cachedCount;
        }

        // Cache miss: compute exact count and cache it
        $count = $this->vectorStore->getVectorCount();
        $this->setStateInt($this->getVectorCountStateKey(), $count);

        return $count;
    }

    public function getEmbeddingGenerationCursor(): ?int
    {
        return $this->getStateInt(self::EMBEDDING_GENERATION_CURSOR_KEY);
    }

    public function saveEmbeddingGenerationCursor(?int $lastLsItemId): void
    {
        $this->setStateInt(self::EMBEDDING_GENERATION_CURSOR_KEY, $lastLsItemId);
    }

    public function resetEmbeddingGenerationCursor(): void
    {
        $this->saveEmbeddingGenerationCursor(null);
    }

    /**
     * @param list<int> $lsItemIds
     * @return array<int, list<int>>
     */
    public function getStaleLsItemIdsGroupedByFramework(array $lsItemIds): array
    {
        if ([] === $lsItemIds) {
            return [];
        }

        $sql = <<<'SQL'
            WITH RECURSIVE item_ancestry AS (
                SELECT li.id AS target_id, li.id AS ancestor_id, li.updated_at AS ancestor_updated_at
                FROM ls_item li
                WHERE li.id IN (:lsItemIds)

                UNION ALL

                SELECT item_ancestry.target_id, parent.id AS ancestor_id, parent.updated_at AS ancestor_updated_at
                FROM item_ancestry
                INNER JOIN ls_association parent_assoc
                    ON parent_assoc.origin_lsitem_id = item_ancestry.ancestor_id
                   AND parent_assoc.type = :childOfType
                INNER JOIN ls_item parent
                    ON parent.id = parent_assoc.destination_lsitem_id
            ),
            hierarchy_state AS (
                SELECT target_id, MAX(ancestor_updated_at) AS hierarchy_updated_at
                FROM item_ancestry
                GROUP BY target_id
            )
            SELECT li.id, li.ls_doc_id AS framework_id
            FROM ls_item li
            INNER JOIN hierarchy_state hs
                ON hs.target_id = li.id
            LEFT JOIN ls_item_embedding embedding
                ON embedding.ls_item_id = li.id
            WHERE li.id IN (:lsItemIds)
              AND (
                  embedding.id IS NULL
                  OR NOT (
                      embedding.is_indexed = 1
                      OR (
                          embedding.vector IS NOT NULL
                          AND embedding.normalized_vector IS NOT NULL
                          AND embedding.magnitude IS NOT NULL
                          AND embedding.binary_code IS NOT NULL
                      )
                  )
                  OR embedding.source_hierarchy_updated_at IS NULL
                  OR embedding.source_hierarchy_updated_at < hs.hierarchy_updated_at
              )
            ORDER BY framework_id ASC, li.id ASC
        SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            $sql,
            [
                'lsItemIds' => $lsItemIds,
                'childOfType' => 'isChildOf',
            ],
            [
                'lsItemIds' => ArrayParameterType::INTEGER,
            ]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $frameworkId = (int) $row['framework_id'];
            $grouped[$frameworkId] ??= [];
            $grouped[$frameworkId][] = (int) $row['id'];
        }

        return $grouped;
    }

    /**
     * @param array<int, LsItem>|null $frameworkItems
     * @param array<int, string> $textCache
     * @param list<int> $visited IDs already traversed (cycle detection)
     */
    public function buildEmbeddingText(LsItem $lsItem, ?array $frameworkItems = null, array &$textCache = [], array $visited = []): string
    {
        $lsItemId = $lsItem->getId();
        if (null !== $lsItemId && isset($textCache[$lsItemId])) {
            return $textCache[$lsItemId];
        }

        if (null !== $lsItemId) {
            if (in_array($lsItemId, $visited, true)) {
                return '';
            }
            $visited[] = $lsItemId;
        }

        $segments = [];
        $parentItem = $this->resolveParentItem($lsItem, $frameworkItems);
        if ($parentItem instanceof LsItem) {
            $segments[] = $this->buildEmbeddingText($parentItem, $frameworkItems, $textCache, $visited);
        }

        $segments[] = $this->buildEmbeddingTextSegment($lsItem);
        $text = implode(
            self::EMBEDDING_TEXT_SEPARATOR,
            array_values(array_filter(
                $segments,
                static fn (?string $segment): bool => null !== $segment && '' !== trim($segment)
            ))
        );

        if (null !== $lsItemId) {
            $textCache[$lsItemId] = $text;
        }

        return $text;
    }

    /**
     * @param array<int, LsItem>|null $frameworkItems
     */
    public function isLeafNode(LsItem $lsItem, ?array $frameworkItems = null): bool
    {
        return $lsItem->getChildren()->isEmpty();
    }

    /**
     * Resolve vector search results to LsItem entities.
     *
     * @param list<array{lsItemId: int, similarity: float}> $vectorResults
     *
     * @return list<array{lsItem: LsItem, similarity: float, embedding: LsItemEmbedding}>
     */
    private function resolveResults(array $vectorResults): array
    {
        $lsItemIds = array_map(
            static fn (array $vectorResult): int => (int) $vectorResult['lsItemId'],
            $vectorResults
        );
        $embeddings = $this->embeddingRepository->findByLsItemIdsIndexed($lsItemIds);

        $results = [];
        foreach ($vectorResults as $vectorResult) {
            $embedding = $embeddings[(int) $vectorResult['lsItemId']] ?? null;
            if (null === $embedding) {
                continue;
            }

            $results[] = [
                'lsItem' => $embedding->getLsItem(),
                'similarity' => $vectorResult['similarity'],
                'embedding' => $embedding,
            ];
        }

        return $results;
    }

    private function resolveManagedLsItem(LsItem $lsItem): LsItem
    {
        if ($this->entityManager->contains($lsItem)) {
            return $lsItem;
        }

        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            throw new \InvalidArgumentException('Cannot use an unmanaged LsItem without an ID for vector search operations.');
        }

        $managedLsItem = $this->entityManager->find(LsItem::class, $lsItemId);
        if (!$managedLsItem instanceof LsItem) {
            throw new \RuntimeException(sprintf('Unable to reload LsItem %d for vector search operations.', $lsItemId));
        }

        return $managedLsItem;
    }

    /**
     * @param array<int, array{
     *   lsItemId: int,
     *   frameworkId: int,
     *   kind: int,
     *   text: string,
     *   isLeafNode: bool,
     *   sourceHierarchyUpdatedAt: \DateTimeImmutable
     * }> $batch
     */
    private function storeFrameworkEmbeddingBatch(array $batch): int
    {
        $texts = array_map(
            static fn (array $entry): string => $entry['text'],
            $batch
        );
        $vectors = $this->embeddingService->generateBatchEmbeddings($texts);

        $rows = [];

        foreach ($batch as $index => $entry) {
            $vector = $vectors[$index] ?? null;
            if (!is_array($vector)) {
                throw new \RuntimeException(sprintf('Missing embedding vector for LsItem %d in batch position %d.', $entry['lsItemId'], $index));
            }

            $rows[] = [
                'lsItemId' => $entry['lsItemId'],
                'frameworkId' => $entry['frameworkId'],
                'kind' => $entry['kind'],
                'text' => $entry['text'],
                'isLeafNode' => $entry['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $entry['sourceHierarchyUpdatedAt'],
                'vector' => $vector,
            ];
        }

        $newlyCountedRows = $this->vectorStore->storeEmbeddingBatch($rows);
        if ($newlyCountedRows > 0) {
            $this->incrementCachedVectorCount($newlyCountedRows);
        }
        $this->entityManager->clear();
        gc_collect_cycles();

        return count($batch);
    }

    private function upsertEmbeddingEntity(
        LsItem $lsItem,
        string $embeddingText,
        bool $isLeafNode,
        \DateTimeImmutable $sourceHierarchyUpdatedAt,
        ?LsItemEmbedding $embedding = null,
    ): LsItemEmbedding {
        if (null === $embedding) {
            $embedding = new LsItemEmbedding($lsItem, $embeddingText);
            $this->entityManager->persist($embedding);
        }

        return $embedding
            ->setText($embeddingText)
            ->setIsLeafNode($isLeafNode)
            ->setSourceHierarchyUpdatedAt($sourceHierarchyUpdatedAt);
    }

    /**
     * @return array<int, array{
     *   fullStatement: string,
     *   parentId: int|null,
     *   isLeafNode: bool,
     *   updatedAt: \DateTimeImmutable,
     *   kind: int
     * }>
     */
    private function loadFrameworkGraph(int $frameworkId): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('li.id', 'li.full_statement', 'li.updated_at', 'li.discriminator', 'parent_assoc.destination_lsitem_id AS parent_id')
            ->from('ls_item', 'li')
            ->leftJoin(
                'li',
                'ls_association',
                'parent_assoc',
                "parent_assoc.origin_lsitem_id = li.id AND parent_assoc.type = 'isChildOf'"
            )
            ->where('li.ls_doc_id = :frameworkId')
            ->setParameter('frameworkId', $frameworkId)
            ->orderBy('li.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $graph = [];
        foreach ($rows as $row) {
            $lsItemId = (int) $row['id'];
            $graph[$lsItemId] ??= [
                'fullStatement' => '',
                'parentId' => null,
                'isLeafNode' => true,
                'updatedAt' => new \DateTimeImmutable((string) $row['updated_at']),
                'kind' => (int) $row['discriminator'],
            ];
            $graph[$lsItemId]['fullStatement'] = trim((string) ($row['full_statement'] ?? ''));
            $graph[$lsItemId]['updatedAt'] = new \DateTimeImmutable((string) $row['updated_at']);
            $graph[$lsItemId]['kind'] = (int) $row['discriminator'];

            $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : null;
            if (null !== $parentId) {
                $graph[$lsItemId]['parentId'] ??= $parentId;
                if (isset($graph[$parentId])) {
                    $graph[$parentId]['isLeafNode'] = false;
                } else {
                    $graph[$parentId] = [
                        'fullStatement' => '',
                        'parentId' => null,
                        'isLeafNode' => false,
                        'updatedAt' => new \DateTimeImmutable('@0'),
                        'kind' => 0,
                    ];
                }
            }
        }

        return $graph;
    }

    /**
     * @param array<int, array{
     *   fullStatement: string,
     *   parentId: int|null,
     *   isLeafNode: bool,
     *   updatedAt: \DateTimeImmutable
     * }> $frameworkGraph
     * @param array<int, string> $textCache
     */
    private function buildEmbeddingTextFromGraph(int $lsItemId, array $frameworkGraph, array &$textCache): string
    {
        if (isset($textCache[$lsItemId])) {
            return $textCache[$lsItemId];
        }

        $frameworkItem = $frameworkGraph[$lsItemId] ?? null;
        if (null === $frameworkItem) {
            throw new \RuntimeException(sprintf('Framework graph is missing LsItem %d.', $lsItemId));
        }

        $segments = [];
        if (($frameworkItem['parentId'] ?? null) !== null) {
            $segments[] = $this->buildEmbeddingTextFromGraph($frameworkItem['parentId'], $frameworkGraph, $textCache);
        }

        $segments[] = trim($frameworkItem['fullStatement']);
        $textCache[$lsItemId] = implode(
            self::EMBEDDING_TEXT_SEPARATOR,
            array_values(array_filter(
                $segments,
                static fn (?string $segment): bool => null !== $segment && '' !== trim($segment)
            ))
        );

        return $textCache[$lsItemId];
    }

    /**
     * @param array<int, array{
     *   fullStatement: string,
     *   parentId: int|null,
     *   isLeafNode: bool,
     *   updatedAt: \DateTimeImmutable
     * }> $frameworkGraph
     * @param array<int, \DateTimeImmutable> $updatedAtCache
     */
    private function getSourceHierarchyUpdatedAtFromGraph(int $lsItemId, array $frameworkGraph, array &$updatedAtCache): \DateTimeImmutable
    {
        if (isset($updatedAtCache[$lsItemId])) {
            return $updatedAtCache[$lsItemId];
        }

        $frameworkItem = $frameworkGraph[$lsItemId] ?? null;
        if (null === $frameworkItem) {
            throw new \RuntimeException(sprintf('Framework graph is missing LsItem %d.', $lsItemId));
        }

        $maxUpdatedAt = $frameworkItem['updatedAt'];
        if (($frameworkItem['parentId'] ?? null) !== null) {
            $parentUpdatedAt = $this->getSourceHierarchyUpdatedAtFromGraph(
                $frameworkItem['parentId'],
                $frameworkGraph,
                $updatedAtCache
            );

            if ($parentUpdatedAt > $maxUpdatedAt) {
                $maxUpdatedAt = $parentUpdatedAt;
            }
        }

        $updatedAtCache[$lsItemId] = $maxUpdatedAt;

        return $maxUpdatedAt;
    }

    /**
     * @param array<int, LsItem>|null $frameworkItems
     */
    private function resolveParentItem(LsItem $lsItem, ?array $frameworkItems): ?LsItem
    {
        $parentItem = $lsItem->getParentItem();
        if (!$parentItem instanceof LsItem) {
            return null;
        }

        if (null === $frameworkItems) {
            return $parentItem;
        }

        $parentItemId = $parentItem->getId();
        if (null === $parentItemId) {
            return $parentItem;
        }

        return $frameworkItems[$parentItemId] ?? $parentItem;
    }

    private function buildEmbeddingTextSegment(LsItem $lsItem): string
    {
        return trim((string) ($lsItem->getFullStatement() ?? ''));
    }

    /**
     * @param array<int, array{
     *   fullStatement: string,
     *   parentId: int|null,
     *   isLeafNode: bool,
     *   updatedAt: \DateTimeImmutable,
     *   kind?: int
     * }> $frameworkGraph
     */
    private function getFrameworkItemKind(int $lsItemId, array $frameworkGraph): int
    {
        $frameworkItem = $frameworkGraph[$lsItemId] ?? null;
        if (null === $frameworkItem || !array_key_exists('kind', $frameworkItem)) {
            throw new \RuntimeException(sprintf('Framework graph is missing kind metadata for LsItem %d.', $lsItemId));
        }

        return (int) $frameworkItem['kind'];
    }

    private function getSourceHierarchyUpdatedAt(LsItem $lsItem, array $visitedIds = []): \DateTimeImmutable
    {
        $maxUpdatedAt = \DateTimeImmutable::createFromInterface($lsItem->getUpdatedAt());
        $parentItem = $lsItem->getParentItem();
        if (!$parentItem instanceof LsItem) {
            return $maxUpdatedAt;
        }

        if (in_array($parentItem->getId(), $visitedIds, true)) {
            return $maxUpdatedAt;
        }

        $parentUpdatedAt = $this->getSourceHierarchyUpdatedAt($parentItem, $visitedIds);

        return $parentUpdatedAt > $maxUpdatedAt ? $parentUpdatedAt : $maxUpdatedAt;
    }

    /**
     * @param array{hasVectorData: bool, sourceHierarchyUpdatedAt: \DateTimeImmutable|null}|null $embeddingStatus
     */
    private function isEmbeddingStatusCurrent(?array $embeddingStatus, \DateTimeImmutable $sourceHierarchyUpdatedAt): bool
    {
        if (null === $embeddingStatus || true !== $embeddingStatus['hasVectorData']) {
            return false;
        }

        $embeddedThrough = $embeddingStatus['sourceHierarchyUpdatedAt'];

        return null !== $embeddedThrough && $embeddedThrough >= $sourceHierarchyUpdatedAt;
    }

    private function incrementCachedVectorCount(int $delta): void
    {
        if (0 === $delta) {
            return;
        }

        $cachedCount = $this->getStateInt($this->getVectorCountStateKey());
        if (null === $cachedCount) {
            return;
        }

        $this->setStateInt($this->getVectorCountStateKey(), max(0, $cachedCount + $delta));
    }

    private function getVectorCountStateKey(): string
    {
        return sprintf('%s_%s', self::VECTOR_COUNT_KEY, strtolower(trim($this->vectorBackend)));
    }

    private function isQdrantBackend(): bool
    {
        return 'qdrant' === strtolower(trim($this->vectorBackend));
    }

    private function getStateInt(string $stateKey): ?int
    {
        $value = $this->entityManager->getConnection()->fetchOne(
            'SELECT last_ls_item_id FROM vector_search_state WHERE state_key = :stateKey',
            [
                'stateKey' => $stateKey,
            ]
        );

        return false !== $value && null !== $value ? (int) $value : null;
    }

    private function setStateInt(string $stateKey, ?int $value): void
    {
        $connection = $this->entityManager->getConnection();
        $existing = $connection->fetchOne(
            'SELECT state_key FROM vector_search_state WHERE state_key = :stateKey',
            [
                'stateKey' => $stateKey,
            ]
        );

        $now = new \DateTimeImmutable();
        if (false === $existing) {
            $connection->insert('vector_search_state', [
                'state_key' => $stateKey,
                'last_ls_item_id' => $value,
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        $connection->update(
            'vector_search_state',
            [
                'last_ls_item_id' => $value,
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'state_key' => $stateKey,
            ]
        );
    }
}
