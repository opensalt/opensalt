<?php

declare(strict_types=1);

namespace App\VectorSearch\Service;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Store\HybridQdrantStore;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\PlatformInterface;

readonly class VectorSearchService
{
    private const EMBEDDING_TEXT_SEPARATOR = "\n\n";
    private const EMBEDDING_GENERATION_CURSOR_KEY = 'embedding_generation_cursor';
    private const VECTOR_COUNT_STATE_KEY = 'vector_count_qdrant';
    private const EMBEDDING_MODEL = 'Xenova/all-MiniLM-L6-v2';

    public function __construct(
        private PlatformInterface $platform,
        private HybridQdrantStore $qdrantStore,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function generateAndStoreEmbedding(LsItem $lsItem, string $text = ''): void
    {
        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            throw new \InvalidArgumentException('Cannot generate embedding for LsItem without ID.');
        }

        $managedLsItem = $this->resolveManagedLsItem($lsItem);

        $frameworkId = $managedLsItem->getLsDoc()->getId();
        if (null === $frameworkId) {
            throw new \InvalidArgumentException(sprintf('Cannot generate embedding for LsItem %d without framework ID.', $lsItemId));
        }

        $embeddingText = trim($text);
        if ('' === $embeddingText) {
            $embeddingText = trim((string) ($managedLsItem->getFullStatement() ?? ''));
        }
        if ('' === $embeddingText) {
            $this->logger->warning('Skipping embedding generation for LsItem with empty text.', [
                'ls_item_id' => $lsItemId,
            ]);

            return;
        }

        $vector = $this->generateEmbedding($embeddingText);

        $this->qdrantStore->importEmbeddings([
            [
                'lsItemId' => $lsItemId,
                'frameworkId' => $frameworkId,
                'kind' => $managedLsItem->getDiscriminator(),
                'text' => $embeddingText,
                'isLeafNode' => $this->isLeafNode($managedLsItem),
                'sourceHierarchyUpdatedAt' => $this->getSourceHierarchyUpdatedAt($managedLsItem),
                'vector' => $vector,
            ],
        ]);

        $this->logger->info('Embedding generated and stored.', [
            'ls_item_id' => $lsItemId,
            'framework_id' => $frameworkId,
        ]);
    }

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

            if (!$force && $this->qdrantStore->pointExists($lsItemId)) {
                ++$skipped;
                continue;
            }

            $batch[] = [
                'lsItemId' => $lsItemId,
                'frameworkId' => $frameworkId,
                'kind' => $this->getFrameworkItemKind($lsItemId, $frameworkGraph),
                'text' => $this->buildEmbeddingTextFromGraph($lsItemId, $frameworkGraph, $textCache),
                'isLeafNode' => (bool) $frameworkItem['isLeafNode'],
                'sourceHierarchyUpdatedAt' => $this->getSourceHierarchyUpdatedAtFromGraph($lsItemId, $frameworkGraph, $updatedAtCache),
            ];

            if (count($batch) >= max(1, $batchSize)) {
                $processed += $this->storeFrameworkEmbeddingBatch($batch);
                $batch = [];
            }
        }

        if ([] !== $batch) {
            $processed += $this->storeFrameworkEmbeddingBatch($batch);
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'missing' => $missing,
        ];
    }

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
     * @return list<array{
     *     lsItemId: int,
     *     frameworkId: int,
     *     kind: int,
     *     text: string,
     *     isLeafNode: bool,
     *     sourceHierarchyUpdatedAt: \DateTimeImmutable|null
     * }>
     */
    public function buildAllEmbeddingRowsForFramework(int $frameworkId): array
    {
        $frameworkGraph = $this->loadFrameworkGraph($frameworkId);
        $textCache = [];
        $updatedAtCache = [];
        $rows = [];

        foreach ($frameworkGraph as $lsItemId => $frameworkItem) {
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

    public function searchByQuery(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        return $this->searchHybridByQuery($query, $limit, $frameworkId, $leafOnly, $kind);
    }

    public function searchByKeyword(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $vectorResults = $this->qdrantStore->searchFullText($query, $limit, $frameworkId, $leafOnly, $kind);

        return $this->resolveResults($vectorResults);
    }

    public function searchHybridByQuery(
        string $query,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
        int $prefetchLimit = 20,
    ): array {
        $queryVector = $this->generateEmbedding($query);

        $vectorResults = $this->qdrantStore->searchHybrid(
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

    public function searchByVector(
        array $vector,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $vectorResults = $this->qdrantStore->search($vector, $limit, $frameworkId, $leafOnly, $kind);

        return $this->resolveResults($vectorResults);
    }

    public function searchByLsItem(
        LsItem $lsItem,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $sourceId = $lsItem->getId();
        if (null === $sourceId) {
            return [];
        }

        $queryVector = $this->qdrantStore->getVectorByLsItemId($sourceId);
        if (null === $queryVector) {
            return [];
        }

        $vectorResults = $this->qdrantStore->search($queryVector, $limit + 1, $frameworkId, $leafOnly, $kind);

        $results = $this->resolveResults($vectorResults);

        $results = array_values(array_filter(
            $results,
            static fn (array $result): bool => $result['lsItem']->getId() !== $sourceId
        ));

        return array_slice($results, 0, $limit);
    }

    public function deleteEmbedding(LsItem $lsItem): void
    {
        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            return;
        }

        $this->qdrantStore->deleteByLsItemId($lsItemId);
    }

    public function hasEmbedding(LsItem $lsItem): bool
    {
        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            return false;
        }

        return $this->qdrantStore->pointExists($lsItemId);
    }

    public function getEmbedding(LsItem $lsItem): ?array
    {
        $lsItemId = $lsItem->getId();
        if (null === $lsItemId) {
            return null;
        }

        $vector = $this->qdrantStore->getVectorByLsItemId($lsItemId);
        if (null === $vector) {
            return null;
        }

        return [
            'lsItemId' => $lsItemId,
            'hasVectorData' => true,
            'vectorDimensions' => count($vector),
        ];
    }

    public function getVectorCount(): int
    {
        $count = $this->qdrantStore->getVectorCount();
        $this->setStateInt(self::VECTOR_COUNT_STATE_KEY, $count);

        return $count;
    }

    public function getVectorCountForFramework(int $frameworkId, bool $leafOnly = false): int
    {
        return $this->qdrantStore->countByFrameworkId($frameworkId, $leafOnly);
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

    public function getStaleLsItemIdsGroupedByFramework(array $lsItemIds): array
    {
        if ([] === $lsItemIds) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT li.id, li.ls_doc_id AS framework_id FROM ls_item li WHERE li.id IN (:lsItemIds) ORDER BY framework_id ASC, li.id ASC',
            ['lsItemIds' => $lsItemIds],
            ['lsItemIds' => ArrayParameterType::INTEGER],
        );

        $grouped = [];
        foreach ($rows as $row) {
            $frameworkId = (int) $row['framework_id'];
            $grouped[$frameworkId] ??= [];
            $grouped[$frameworkId][] = (int) $row['id'];
        }

        return $grouped;
    }

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

    public function isLeafNode(LsItem $lsItem, ?array $frameworkItems = null): bool
    {
        return $lsItem->getChildren()->isEmpty();
    }

    private function generateEmbedding(string $text): array
    {
        $deferredResult = $this->platform->invoke(self::EMBEDDING_MODEL, $text);
        $vectors = $deferredResult->asVectors();
        if ([] === $vectors) {
            throw new \RuntimeException(sprintf('Platform did not return an embedding vector for model %s.', self::EMBEDDING_MODEL));
        }

        return $vectors[0]->getData();
    }

    private function resolveResults(array $vectorResults): array
    {
        $lsItemIds = array_map(
            static fn (array $vectorResult): int => (int) $vectorResult['lsItemId'],
            $vectorResults
        );

        $lsItems = $this->entityManager->getRepository(LsItem::class)->findBy(['id' => $lsItemIds]);
        $indexed = [];
        foreach ($lsItems as $lsItem) {
            $id = $lsItem->getId();
            if (null !== $id) {
                $indexed[$id] = $lsItem;
            }
        }

        $results = [];
        foreach ($vectorResults as $vectorResult) {
            $lsItem = $indexed[(int) $vectorResult['lsItemId']] ?? null;
            if (null === $lsItem) {
                continue;
            }

            $results[] = [
                'lsItem' => $lsItem,
                'similarity' => $vectorResult['similarity'],
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

    private function storeFrameworkEmbeddingBatch(array $batch): int
    {
        $texts = array_map(
            static fn (array $entry): string => $entry['text'],
            $batch
        );

        $vectors = [];
        foreach (array_chunk($texts, 8) as $textChunk) {
            $chunkInput = implode("\n", $textChunk);
            $deferredResult = $this->platform->invoke(self::EMBEDDING_MODEL, $chunkInput);
            $chunkVectors = $deferredResult->asVectors();

            if (count($chunkVectors) !== count($textChunk)) {
                $deferredResult = $this->platform->invoke(self::EMBEDDING_MODEL, $textChunk);
                $chunkVectors = $deferredResult->asVectors();
            }

            foreach ($chunkVectors as $vector) {
                $vectors[] = $vector->getData();
            }
        }

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

        $this->qdrantStore->importEmbeddings($rows);
        gc_collect_cycles();

        return count($batch);
    }

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
