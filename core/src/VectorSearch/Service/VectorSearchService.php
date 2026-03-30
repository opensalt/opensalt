<?php

declare(strict_types=1);

namespace App\VectorSearch\Service;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Repository\LsItemEmbeddingRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

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
        private VectorTableService $vectorTableService,
        private LsItemEmbeddingRepository $embeddingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
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

        $this->vectorTableService->storeEmbeddingVector($embedding, $vector);
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
     * Search for similar LsItems by text query.
     *
     * @return array Array of LsItem entities with similarity scores
     */
    public function searchByQuery(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $queryVector = $this->embeddingService->generateEmbedding($query);

        $results = $this->searchByVector($queryVector, $limit, $frameworkId, $leafOnly, $kind);

        $this->logger->debug('Vector query search completed', [
            'query' => $query,
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
        $vectorResults = $this->vectorTableService->search($vector, $limit, $frameworkId, $leafOnly, $kind);

        $embeddingIds = array_map(
            static fn (array $vectorResult): int => (int) $vectorResult['id'],
            $vectorResults
        );
        $embeddings = $this->embeddingRepository->findByIdsWithLsItemIndexed($embeddingIds);

        $results = [];
        foreach ($vectorResults as $vectorResult) {
            $embedding = $embeddings[(int) $vectorResult['id']] ?? null;
            if (null === $embedding) {
                continue;
            }

            $results[] = [
                'lsItem' => $embedding->getLsItem(),
                'similarity' => $vectorResult['similarity'],
                'embedding' => $embedding,
            ];
        }

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
        $queryVector = $embedding?->getNormalizedVector();

        if (null === $embedding || null === $queryVector) {
            return [];
        }

        $results = $this->searchByVector($queryVector, $limit + 1, $frameworkId, $leafOnly, $kind);

        $sourceId = $lsItem->getId();
        if (null === $sourceId) {
            return array_slice($results, 0, $limit);
        }

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
        $cachedCount = $this->getStateInt(self::VECTOR_COUNT_KEY);
        if (null !== $cachedCount) {
            return $cachedCount;
        }

        // Cache miss: compute exact count and cache it
        $count = $this->vectorTableService->getVectorCount();
        $this->setStateInt(self::VECTOR_COUNT_KEY, $count);

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
                  OR embedding.vector IS NULL
                  OR embedding.normalized_vector IS NULL
                  OR embedding.magnitude IS NULL
                  OR embedding.binary_code IS NULL
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
                'text' => $entry['text'],
                'isLeafNode' => $entry['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $entry['sourceHierarchyUpdatedAt'],
                'vector' => $vector,
            ];
        }

        $newlyCountedRows = $this->vectorTableService->storeEmbeddingBatch($rows);
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
     *   updatedAt: \DateTimeImmutable
     * }>
     */
    private function loadFrameworkGraph(int $frameworkId): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('li.id', 'li.full_statement', 'li.updated_at', 'parent_assoc.destination_lsitem_id AS parent_id')
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
            ];
            $graph[$lsItemId]['fullStatement'] = trim((string) ($row['full_statement'] ?? ''));
            $graph[$lsItemId]['updatedAt'] = new \DateTimeImmutable((string) $row['updated_at']);

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

        $cachedCount = $this->getStateInt(self::VECTOR_COUNT_KEY);
        if (null === $cachedCount) {
            return;
        }

        $this->setStateInt(self::VECTOR_COUNT_KEY, max(0, $cachedCount + $delta));
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
