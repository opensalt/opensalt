<?php

declare(strict_types=1);

namespace App\VectorSearch\Store;

use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Service\VectorTableService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class QdrantVectorStore implements VectorStoreInterface
{
    private const EMBEDDING_DIMENSION = 384;
    private const DEFAULT_BATCH_SIZE = 250;

    public function __construct(
        private HttpClientInterface $httpClient,
        private VectorTableService $metadataStore,
        private LoggerInterface $logger,
        #[Autowire('%env(string:VECTOR_SEARCH_QDRANT_URL)%')]
        private string $baseUrl,
        #[Autowire('%env(string:VECTOR_SEARCH_QDRANT_COLLECTION)%')]
        private string $collectionName,
        #[Autowire('%env(string:VECTOR_SEARCH_QDRANT_ALIAS)%')]
        private string $collectionAlias,
        #[Autowire('%env(string:VECTOR_SEARCH_QDRANT_API_KEY)%')]
        private string $apiKey,
        #[Autowire('%env(int:VECTOR_SEARCH_QDRANT_TIMEOUT)%')]
        private int $timeoutSeconds,
        #[Autowire('%env(int:VECTOR_SEARCH_HYBRID_PREFETCH_LIMIT)%')]
        private int $hybridPrefetchLimit = 20,
        #[Autowire('%env(int:VECTOR_SEARCH_HYBRID_RRF_K)%')]
        private int $hybridRrfK = 60,
    ) {
    }

    /**
     * @param list<float> $vector
     */
    public function storeEmbeddingVector(LsItemEmbedding $embedding, array $vector): void
    {
        $this->metadataStore->storeEmbeddingMetadata($embedding, false);
    }

    /**
     * @param list<float> $vector
     */
    public function finalizeStoredEmbedding(LsItemEmbedding $embedding, array $vector): void
    {
        $lsItemId = $embedding->getLsItem()->getId();
        if (null === $lsItemId) {
            throw new \InvalidArgumentException('Cannot sync a Qdrant point without an LsItem ID.');
        }

        $frameworkId = $embedding->getLsItem()->getLsDoc()->getId();
        if (null === $frameworkId) {
            throw new \InvalidArgumentException(sprintf('Cannot sync LsItem %d without a framework ID.', $lsItemId));
        }

        $this->upsertPoints([
            $this->buildPoint(
                $lsItemId,
                $frameworkId,
                $embedding->getLsItem()->getDiscriminator(),
                $embedding->getText() ?? '',
                $embedding->isLeafNode(),
                $embedding->getSourceHierarchyUpdatedAt(),
                $vector
            ),
        ]);

        $embedding->markIndexed();
    }

    public function storeEmbeddingBatch(array $rows): int
    {
        if ([] === $rows) {
            return 0;
        }

        $points = [];
        foreach ($rows as $row) {
            $points[] = $this->buildPoint(
                $row['lsItemId'],
                $row['frameworkId'],
                $row['kind'],
                $row['text'],
                $row['isLeafNode'],
                $row['sourceHierarchyUpdatedAt'],
                $row['vector']
            );
        }

        $this->upsertPoints($points);

        return $this->metadataStore->storeEmbeddingMetadataBatch($rows, true);
    }

    public function search(
        array $queryVector,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        if ($limit < 1) {
            return [];
        }

        $payload = [
            'query' => array_values($queryVector),
            'using' => 'dense',
            'limit' => $limit,
            'with_payload' => false,
            'with_vector' => false,
        ];

        $filter = $this->buildFilter($frameworkId, $leafOnly, $kind);
        if (null !== $filter) {
            $payload['filter'] = $filter;
        }

        $response = $this->request(
            'POST',
            sprintf('/collections/%s/points/query', rawurlencode($this->getActiveCollectionReference())),
            $payload,
            true
        );
        $results = $response['result']['points'] ?? [];
        if (!is_array($results)) {
            return [];
        }

        $matches = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $id = $result['id'] ?? null;
            $score = $result['score'] ?? null;
            if (!is_numeric($id) || !is_numeric($score)) {
                continue;
            }

            $matches[] = [
                'lsItemId' => (int) $id,
                'similarity' => (float) $score,
            ];
        }

        return $matches;
    }

    public function searchFullText(
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        if ($limit < 1) {
            return [];
        }

        $normalizedQuery = trim($queryText);
        if ('' === $normalizedQuery) {
            return [];
        }

        $payload = [
            'query' => [
                'text' => $normalizedQuery,
                'model' => 'qdrant/bm25',
            ],
            'using' => 'sparse',
            'limit' => $limit,
            'with_payload' => false,
            'with_vector' => false,
        ];

        $filter = $this->buildFilter($frameworkId, $leafOnly, $kind);
        if (null !== $filter) {
            $payload['filter'] = $filter;
        }

        try {
            $response = $this->request(
                'POST',
                sprintf('/collections/%s/points/query', rawurlencode($this->getActiveCollectionReference())),
                $payload,
                true
            );
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            if (!$this->isSparseVectorError($message)) {
                throw $e;
            }

            $this->logger->warning('Qdrant full-text search failed, falling back to database keyword search', [
                'error' => $message,
            ]);

            return $this->metadataStore->searchFullText($normalizedQuery, $limit, $frameworkId, $leafOnly, $kind);
        }

        $results = $response['result']['points'] ?? [];
        if (!is_array($results)) {
            return [];
        }

        $matches = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $id = $result['id'] ?? null;
            $score = $result['score'] ?? null;
            if (!is_numeric($id) || !is_numeric($score)) {
                continue;
            }

            $matches[] = [
                'lsItemId' => (int) $id,
                'similarity' => (float) $score,
            ];
        }

        return $matches;
    }

    public function searchHybrid(
        array $queryVector,
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
        int $prefetchLimit = 0,
    ): array {
        if ($limit < 1) {
            return [];
        }

        $effectivePrefetchLimit = $prefetchLimit > 0 ? $prefetchLimit : $this->hybridPrefetchLimit;
        $effectivePrefetchLimit = max($limit, $effectivePrefetchLimit);

        $payload = [
            'prefetch' => [
                [
                    'query' => array_values($queryVector),
                    'using' => 'dense',
                    'limit' => $effectivePrefetchLimit,
                    'with_payload' => false,
                    'with_vector' => false,
                ],
                [
                    'query' => [
                        'text' => $queryText,
                        'model' => 'qdrant/bm25',
                    ],
                    'using' => 'sparse',
                    'limit' => $effectivePrefetchLimit,
                    'with_payload' => false,
                    'with_vector' => false,
                ],
            ],
            'query' => [
                'fusion' => 'rrf',
                'params' => [
                    'k' => $this->hybridRrfK,
                ],
            ],
            'limit' => $limit,
            'with_payload' => false,
            'with_vector' => false,
        ];

        $filter = $this->buildFilter($frameworkId, $leafOnly, $kind);
        if (null !== $filter) {
            $payload['filter'] = $filter;
        }

        try {
            $response = $this->request(
                'POST',
                sprintf('/collections/%s/points/query', rawurlencode($this->getActiveCollectionReference())),
                $payload,
                true
            );
        } catch (\RuntimeException $e) {
            // Only fallback for sparse-vector-related errors (e.g., old collection
            // not yet rebuilt with BM25 support). Re-throw genuine server failures.
            $message = $e->getMessage();
            if (!$this->isSparseVectorError($message)) {
                throw $e;
            }

            $this->logger->warning('Hybrid search failed, falling back to vector-only search', [
                'error' => $message,
            ]);

            return $this->search($queryVector, $limit, $frameworkId, $leafOnly, $kind);
        }

        $results = $response['result']['points'] ?? [];
        if (!is_array($results)) {
            return [];
        }

        $matches = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $id = $result['id'] ?? null;
            $score = $result['score'] ?? null;
            if (!is_numeric($id) || !is_numeric($score)) {
                continue;
            }

            $matches[] = [
                'lsItemId' => (int) $id,
                'similarity' => (float) $score,
            ];
        }

        return $matches;
    }

    public function getVectorByLsItemId(int $lsItemId): ?array
    {
        $response = $this->request(
            'GET',
            sprintf('/collections/%s/points/%d?with_vector=true&with_payload=false', rawurlencode($this->getActiveCollectionReference()), $lsItemId),
            null,
            true
        );

        $result = $response['result'] ?? null;
        if (!is_array($result)) {
            return null;
        }

        $vector = $result['vector'] ?? null;
        if (is_array($vector) && isset($vector['dense']) && is_array($vector['dense'])) {
            $vector = $vector['dense'];
        }
        if (!is_array($vector)) {
            return null;
        }

        return array_map(static fn (mixed $value): float => (float) $value, $vector);
    }

    public function deleteByLsItemId(int $lsItemId): void
    {
        $this->request(
            'POST',
            sprintf('/collections/%s/points/delete?wait=true', rawurlencode($this->getActiveCollectionReference())),
            [
                'points' => [$lsItemId],
            ],
            true
        );
    }

    public function getVectorCount(): int
    {
        return $this->countCollection($this->getActiveCollectionReference());
    }

    /**
     * @param list<array{
     *   lsItemId: int,
     *   frameworkId: int,
     *   kind: int,
     *   text: string,
     *   isLeafNode: bool,
     *   sourceHierarchyUpdatedAt: ?\DateTimeImmutable,
     *   vector: list<float>
     * }> $rows
     */
    public function importEmbeddings(array $rows, ?string $collectionName = null): int
    {
        if ([] === $rows) {
            return 0;
        }

        $points = [];
        foreach ($rows as $row) {
            $points[] = $this->buildPoint(
                $row['lsItemId'],
                $row['frameworkId'],
                $row['kind'],
                $row['text'],
                $row['isLeafNode'],
                $row['sourceHierarchyUpdatedAt'],
                $row['vector']
            );
        }

        $this->upsertPoints($points, $collectionName);

        return count($points);
    }

    public function cloneCollection(
        string $sourceCollection,
        string $targetCollection,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        int $afterId = 0,
        int $limit = 0,
    ): int {
        $copied = 0;
        $nextOffset = $afterId > 0 ? $afterId : null;

        while (true) {
            $remaining = $limit > 0 ? max(0, $limit - $copied) : null;
            if (null !== $remaining && 0 === $remaining) {
                break;
            }

            $response = $this->scrollCollection(
                $sourceCollection,
                $nextOffset,
                min($batchSize, $remaining ?? $batchSize)
            );

            $points = $response['points'];
            if ([] === $points) {
                break;
            }

            $this->upsertPoints($points, $targetCollection);
            $copied += count($points);
            $nextOffset = $response['nextOffset'];

            if (null === $nextOffset) {
                break;
            }
        }

        return $copied;
    }

    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    public function getCollectionAlias(): ?string
    {
        $alias = trim($this->collectionAlias);

        return '' !== $alias ? $alias : null;
    }

    public function getActiveCollectionReference(): string
    {
        return $this->getCollectionAlias() ?? $this->collectionName;
    }

    public function getResolvedActiveCollectionName(): ?string
    {
        $alias = $this->getCollectionAlias();
        if (null === $alias) {
            return $this->collectionExists($this->collectionName) ? $this->collectionName : null;
        }

        return $this->getAliasTarget($alias);
    }

    public function buildShadowCollectionName(?string $suffix = null): string
    {
        $suffix ??= new \DateTimeImmutable()->format('YmdHis');

        return sprintf('%s__rebuild__%s', $this->collectionName, preg_replace('/[^A-Za-z0-9_]+/', '_', $suffix) ?: 'run');
    }

    public function collectionExists(string $collectionName): bool
    {
        return [] !== $this->request(
            'GET',
            sprintf('/collections/%s', rawurlencode($collectionName)),
            null,
            true
        );
    }

    public function createCollection(string $collectionName, bool $recreate = false): void
    {
        $this->prepareCollection($collectionName, $recreate);
    }

    public function prepareCollection(string $collectionName, bool $recreate = false, bool $allowExisting = false): bool
    {
        $exists = $this->collectionExists($collectionName);

        if ($recreate && $exists) {
            $this->deleteCollection($collectionName);
            $exists = false;
        }

        if ($exists) {
            if ($allowExisting) {
                return false;
            }

            throw new \RuntimeException(sprintf('Qdrant collection "%s" already exists.', $collectionName));
        }

        $this->createCollectionInternal($collectionName);

        return true;
    }

    public function deleteCollection(string $collectionName): void
    {
        $this->request(
            'DELETE',
            sprintf('/collections/%s?timeout=%d', rawurlencode($collectionName), max(1, $this->timeoutSeconds)),
            null,
            true
        );
    }

    /**
     * @return list<string>
     */
    public function listCollections(): array
    {
        $response = $this->request('GET', '/collections', null, true);
        $collections = $response['result']['collections'] ?? [];
        if (!is_array($collections)) {
            return [];
        }

        $names = [];
        foreach ($collections as $collection) {
            if (!is_array($collection)) {
                continue;
            }

            $name = $collection['name'] ?? null;
            if (is_string($name) && '' !== trim($name)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    public function countCollection(string $collectionReference): int
    {
        $response = $this->request(
            'POST',
            sprintf('/collections/%s/points/count', rawurlencode($collectionReference)),
            [
                'exact' => true,
            ],
            true
        );

        $count = $response['result']['count'] ?? 0;

        return is_numeric($count) ? (int) $count : 0;
    }

    public function getAliasTarget(string $alias): ?string
    {
        $response = $this->request('GET', '/aliases', null, true);
        $aliases = $response['result']['aliases'] ?? [];
        if (!is_array($aliases)) {
            return null;
        }

        foreach ($aliases as $aliasRow) {
            if (!is_array($aliasRow)) {
                continue;
            }

            if (($aliasRow['alias_name'] ?? null) === $alias) {
                $collectionName = $aliasRow['collection_name'] ?? null;

                return is_string($collectionName) ? $collectionName : null;
            }
        }

        return null;
    }

    public function activateCollection(string $collectionName, ?string $alias = null): ?string
    {
        $alias ??= $this->getCollectionAlias();
        if (null === $alias || '' === trim($alias)) {
            throw new \RuntimeException('Cannot hot-swap Qdrant collections without a configured alias.');
        }

        $previousCollection = $this->getAliasTarget($alias);
        $actions = [];

        if (null !== $previousCollection) {
            $actions[] = [
                'delete_alias' => [
                    'alias_name' => $alias,
                ],
            ];
        }

        $actions[] = [
            'create_alias' => [
                'collection_name' => $collectionName,
                'alias_name' => $alias,
            ],
        ];

        $this->request(
            'POST',
            sprintf('/collections/aliases?timeout=%d', max(1, $this->timeoutSeconds)),
            [
                'actions' => $actions,
            ]
        );

        return $previousCollection;
    }

    /**
     * @param list<array{id: int, vector: array<string, list<float>>, payload: array<string, bool|int|string|null>}> $points
     */
    private function upsertPoints(array $points, ?string $collectionName = null): void
    {
        if ([] === $points) {
            return;
        }

        $targetCollection = $collectionName ?? $this->getActiveCollectionReference();
        $this->ensureCollection($targetCollection);

        foreach (array_chunk($points, self::DEFAULT_BATCH_SIZE) as $chunk) {
            $this->request(
                'PUT',
                sprintf('/collections/%s/points?wait=true', rawurlencode($targetCollection)),
                [
                    'points' => $chunk,
                ]
            );
        }
    }

    private function ensureCollection(?string $collectionReference = null): void
    {
        $collectionReference ??= $this->getActiveCollectionReference();

        if (null !== $this->getCollectionAlias() && $collectionReference === $this->getCollectionAlias()) {
            $resolvedCollection = $this->getAliasTarget($collectionReference);
            if (null !== $resolvedCollection && $this->collectionExists($resolvedCollection)) {
                return;
            }

            if ($this->collectionExists($this->collectionName)) {
                $this->activateCollection($this->collectionName, $collectionReference);

                return;
            }

            $this->createCollectionInternal($this->collectionName);
            $this->activateCollection($this->collectionName, $collectionReference);

            return;
        }

        if ($this->collectionExists($collectionReference)) {
            return;
        }

        $this->createCollectionInternal($collectionReference);
    }

    private function createCollectionInternal(string $collectionName): void
    {
        $this->request('PUT', sprintf('/collections/%s', rawurlencode($collectionName)), [
            'vectors' => [
                'dense' => [
                    'size' => self::EMBEDDING_DIMENSION,
                    'distance' => 'Cosine',
                    'on_disk' => true,
                ],
            ],
            'sparse_vectors' => [
                'sparse' => [
                    'modifier' => 'idf',
                    'index' => [
                        'on_disk' => true,
                    ],
                ],
            ],
            'hnsw_config' => [
                'on_disk' => true,
            ],
            'optimizers_config' => [
                'indexing_threshold' => 0,
            ],
            'on_disk_payload' => true,
        ]);
    }

    /**
     * @return array{points: list<array{id: int, vector: array<string, list<float>>, payload: array<string, bool|int|string|null>}>, nextOffset: int|null}
     */
    private function scrollCollection(string $collectionReference, ?int $offset, int $limit): array
    {
        $payload = [
            'limit' => max(1, $limit),
            'with_payload' => true,
            'with_vector' => true,
        ];

        if (null !== $offset) {
            $payload['offset'] = $offset;
        }

        $response = $this->request(
            'POST',
            sprintf('/collections/%s/points/scroll', rawurlencode($collectionReference)),
            $payload,
            true
        );

        $result = $response['result'] ?? [];
        $points = $result['points'] ?? [];
        $nextOffset = $result['next_page_offset'] ?? null;

        $normalizedPoints = [];
        if (is_array($points)) {
            foreach ($points as $point) {
                if (!is_array($point)) {
                    continue;
                }

                $id = $point['id'] ?? null;
                $vector = $point['vector'] ?? null;
                if (is_array($vector) && isset($vector['dense']) && is_array($vector['dense'])) {
                    $vector = $vector['dense'];
                }
                $payload = $point['payload'] ?? [];
                if (!is_numeric($id) || !is_array($vector) || !is_array($payload)) {
                    continue;
                }

                $normalizedPoints[] = [
                    'id' => (int) $id,
                    'vector' => [
                        'dense' => array_map(static fn (mixed $value): float => (float) $value, $vector),
                    ],
                    'payload' => $payload,
                ];
            }
        }

        return [
            'points' => $normalizedPoints,
            'nextOffset' => is_numeric($nextOffset) ? (int) $nextOffset : null,
        ];
    }

    /**
     * @param list<float> $vector
     * @return array{id: int, vector: array<string, mixed>, payload: array<string, bool|int|string|null>}
     */
    private function buildPoint(
        int $lsItemId,
        int $frameworkId,
        int $kind,
        string $text,
        bool $isLeafNode,
        ?\DateTimeImmutable $sourceHierarchyUpdatedAt,
        array $vector,
    ): array {
        return [
            'id' => $lsItemId,
            'vector' => [
                'dense' => array_values($vector),
                'sparse' => [
                    'text' => $text,
                    'model' => 'qdrant/bm25',
                ],
            ],
            'payload' => [
                'ls_item_id' => $lsItemId,
                'framework_id' => $frameworkId,
                'kind' => $kind,
                'is_leaf_node' => $isLeafNode,
                'text' => $text,
                'source_hierarchy_updated_at' => $sourceHierarchyUpdatedAt?->format(\DateTimeInterface::ATOM),
            ],
        ];
    }

    /**
     * @return array{must: list<array<string, array{value: bool|int}>>}|null
     */
    private function buildFilter(?int $frameworkId, bool $leafOnly, ?int $kind): ?array
    {
        $must = [];

        if (null !== $frameworkId) {
            $must[] = [
                'key' => 'framework_id',
                'match' => [
                    'value' => $frameworkId,
                ],
            ];
        }

        if ($leafOnly) {
            $must[] = [
                'key' => 'is_leaf_node',
                'match' => [
                    'value' => true,
                ],
            ];
        }

        if (null !== $kind) {
            $must[] = [
                'key' => 'kind',
                'match' => [
                    'value' => $kind,
                ],
            ];
        }

        return [] !== $must ? ['must' => $must] : null;
    }

    /**
     * Determine whether a Qdrant error is related to a missing or misconfigured sparse vector.
     */
    private function isSparseVectorError(string $message): bool
    {
        return str_contains($message, 'sparse')
            || str_contains($message, '404')
            || str_contains($message, 'not found');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function request(string $method, string $path, ?array $json = null, bool $allowNotFound = false): array
    {
        $url = rtrim($this->baseUrl, '/').$path;
        $options = [
            'timeout' => max(1, $this->timeoutSeconds),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        if (null !== $json) {
            $options['json'] = $json;
        }

        if ('' !== trim($this->apiKey)) {
            $options['headers']['api-key'] = trim($this->apiKey);
        }

        $response = $this->httpClient->request($method, $url, $options);
        $statusCode = $response->getStatusCode();
        if ($allowNotFound && 404 === $statusCode) {
            return [];
        }

        $decoded = $response->toArray(false);

        if ($statusCode >= 400) {
            $this->logger->error('Qdrant request failed', [
                'status_code' => $statusCode,
                'method' => $method,
                'path' => $path,
                'response' => $decoded,
            ]);

            throw new \RuntimeException(sprintf('Qdrant request to %s failed with status %d.', $path, $statusCode));
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Qdrant response for %s was not a JSON object.', $path));
        }

        return $decoded;
    }
}
