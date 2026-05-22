<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Mcp\Capability\Attribute\McpTool;

readonly class OpenSaltMcpTools
{
    public function __construct(
        private OpenSaltMcpQueryService $queryService,
        private OpenSaltMcpPayloadFactory $payloadFactory,
        private VectorSearchService $vectorSearchService,
    ) {
    }

    /**
     * List public OpenSALT framework documents.
     *
     * @return array{
     *     limit: int,
     *     offset: int,
     *     query: ?string,
     *     count: int,
     *     documents: array<int, array<string, mixed>>
     * }
     */
    #[McpTool(name: 'opensalt_list_documents')]
    public function listDocuments(int $limit = 20, int $offset = 0, ?string $query = null): array
    {
        $limit = $this->normalizeLimit($limit, 20, 100);
        $offset = max(0, $offset);

        $documents = $this->queryService->listPublicDocuments($limit, $offset, $query);

        return [
            'limit' => $limit,
            'offset' => $offset,
            'query' => $query,
            'count' => count($documents),
            'documents' => array_map($this->payloadFactory->documentSummary(...), $documents),
        ];
    }

    /**
     * Fetch one public OpenSALT document by identifier.
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'opensalt_get_document')]
    public function getDocument(string $documentIdentifier, bool $includeItems = false, int $itemLimit = 25): array
    {
        $documentIdentifier = trim($documentIdentifier);
        $document = $this->queryService->getPublicDocumentByIdentifier($documentIdentifier);
        if (null === $document) {
            return [
                'error' => 'Document not found or not publicly visible.',
                'document_identifier' => $documentIdentifier,
            ];
        }

        $response = [
            'document' => $this->payloadFactory->documentDetails($document),
        ];

        if ($includeItems) {
            $items = $this->queryService->listPublicItems(
                limit: $this->normalizeLimit($itemLimit, 25, 100),
                documentIdentifier: $documentIdentifier,
            );

            $response['item_count'] = count($items);
            $response['items'] = array_map($this->payloadFactory->itemSummary(...), $items);
        }

        return $response;
    }

    /**
     * List public OpenSALT items, optionally within one document.
     *
     * @return array{
     *     limit: int,
     *     offset: int,
     *     query: ?string,
     *     document_identifier: ?string,
     *     count: int,
     *     items: array<int, array<string, mixed>>
     * }
     */
    #[McpTool(name: 'opensalt_list_items')]
    public function listItems(
        int $limit = 20,
        int $offset = 0,
        ?string $documentIdentifier = null,
        ?string $query = null,
    ): array {
        $limit = $this->normalizeLimit($limit, 20, 100);
        $offset = max(0, $offset);

        $items = $this->queryService->listPublicItems($limit, $offset, $documentIdentifier, $query);

        return [
            'limit' => $limit,
            'offset' => $offset,
            'query' => $query,
            'document_identifier' => $documentIdentifier,
            'count' => count($items),
            'items' => array_map($this->payloadFactory->itemSummary(...), $items),
        ];
    }

    /**
     * Fetch one public OpenSALT item by identifier.
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'opensalt_get_item')]
    public function getItem(
        string $itemIdentifier,
        ?string $documentIdentifier = null,
        bool $includeRelated = true,
        int $relatedLimit = 10,
    ): array {
        $item = $this->queryService->getPublicItemByIdentifier($itemIdentifier, $documentIdentifier);
        if (null === $item) {
            return [
                'error' => 'Item not found or not publicly visible.',
                'item_identifier' => trim($itemIdentifier),
                'document_identifier' => $documentIdentifier,
            ];
        }

        $response = [
            'item' => $this->payloadFactory->itemDetails($item),
        ];

        if ($includeRelated) {
            $related = $this->queryService->findAssociationRelatedItems(
                $item,
                $this->normalizeLimit($relatedLimit, 10, 100),
            );

            $response['related_item_count'] = count($related);
            $response['related_items'] = array_map(
                fn (array $match): array => [
                    'item' => $this->payloadFactory->itemSummary($match['item']),
                    'relation_count' => $match['relationCount'],
                    'relations' => $match['relations'],
                ],
                $related
            );
        }

        return $response;
    }

    /**
     * Find related items by association graph and vector similarity.
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'opensalt_search_related_items')]
    public function searchRelatedItems(
        string $itemIdentifier,
        ?string $documentIdentifier = null,
        int $limit = 10,
        bool $includeAssociations = true,
        bool $includeVectorSearch = true,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $limit = $this->normalizeLimit($limit, 10, 100);

        $sourceItem = $this->queryService->getPublicItemByIdentifier($itemIdentifier, $documentIdentifier);
        if (null === $sourceItem) {
            return [
                'error' => 'Source item not found or not publicly visible.',
                'item_identifier' => trim($itemIdentifier),
                'document_identifier' => $documentIdentifier,
            ];
        }

        $associationMatches = [];
        if ($includeAssociations) {
            $associationMatches = $this->queryService->findAssociationRelatedItems($sourceItem, $limit);
        }

        $vectorMode = 'disabled';
        $vectorError = null;
        $vectorMatches = [];
        if ($includeVectorSearch) {
            try {
                $frameworkId = $sourceItem->getLsDoc()->getId();
                $frameworkFilter = null !== $frameworkId ? $frameworkId : null;

                if ($this->vectorSearchService->hasEmbedding($sourceItem)) {
                    $vectorMode = 'embedding';
                    $vectorMatches = $this->vectorSearchService->searchByLsItem($sourceItem, $limit, $frameworkFilter, $leafOnly, $kind);
                } elseif ('' !== trim((string) $sourceItem->getFullStatement())) {
                    $vectorMode = 'text-fallback';
                    $vectorMatches = $this->vectorSearchService->searchByQuery((string) $sourceItem->getFullStatement(), $limit, $frameworkFilter, $leafOnly, $kind);
                } else {
                    $vectorMode = 'unavailable';
                }

                $vectorMatches = $this->filterVectorMatches($vectorMatches, $sourceItem, $limit);
            } catch (\Throwable $exception) {
                $vectorMode = 'error';
                $vectorError = $exception->getMessage();
                $vectorMatches = [];
            }
        }

        $combinedMatches = $this->combineRelatedMatches($associationMatches, $vectorMatches, $limit);

        return [
            'source_item' => $this->payloadFactory->itemSummary($sourceItem),
            'vector_mode' => $vectorMode,
            'leaf_only' => $leafOnly,
            'combined_count' => count($combinedMatches),
            'combined_matches' => $combinedMatches,
            'association_count' => count($associationMatches),
            'association_matches' => array_map(
                fn (array $match): array => [
                    'item' => $this->payloadFactory->itemSummary($match['item']),
                    'relation_count' => $match['relationCount'],
                    'relations' => $match['relations'],
                ],
                $associationMatches
            ),
            'vector_count' => count($vectorMatches),
            'vector_matches' => array_map(
                fn (array $match): array => [
                    'item' => $this->payloadFactory->itemSummary($match['lsItem']),
                    'similarity' => (float) $match['similarity'],
                ],
                $vectorMatches
            ),
            'vector_error' => $vectorError,
        ];
    }

    /**
     * Semantic item search with vector search and keyword fallback.
     *
     * @param int|null $kind Filter by item kind: 0=Default, 1=Job, 2=Course, 3=Assessment, 4=Credential, 5=Organization, 6=Identifier, 7=PublicKey, null=any
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'opensalt_semantic_search_items')]
    public function semanticSearchItems(
        string $query,
        int $limit = 10,
        ?string $documentIdentifier = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        $query = trim($query);
        if ('' === $query) {
            return [
                'error' => 'Query must not be empty.',
            ];
        }

        $limit = $this->normalizeLimit($limit, 10, 100);
        $frameworkId = null;
        if (null !== $documentIdentifier && '' !== trim($documentIdentifier)) {
            $document = $this->queryService->getPublicDocumentByIdentifier($documentIdentifier);
            if (null === $document) {
                return [
                    'error' => 'Document not found or not publicly visible.',
                    'document_identifier' => $documentIdentifier,
                ];
            }

            $frameworkId = $document->getId();
        }

        $mode = 'vector';
        $vectorError = null;
        try {
            $vectorResults = $this->vectorSearchService->searchByQuery($query, $limit, $frameworkId, $leafOnly, $kind);
            $vectorResults = $this->filterVectorMatches($vectorResults, null, $limit);

            return [
                'mode' => $mode,
                'query' => $query,
                'leaf_only' => $leafOnly,
                'document_identifier' => $documentIdentifier,
                'count' => count($vectorResults),
                'items' => array_map(
                    fn (array $result): array => [
                        'item' => $this->payloadFactory->itemSummary($result['lsItem']),
                        'similarity' => (float) $result['similarity'],
                    ],
                    $vectorResults
                ),
            ];
        } catch (\Throwable $exception) {
            $mode = 'keyword-fallback';
            $vectorError = $exception->getMessage();
        }

        $keywordMatches = $this->queryService->listPublicItems($limit, 0, $documentIdentifier, $query);

        return [
            'mode' => $mode,
            'query' => $query,
            'leaf_only' => $leafOnly,
            'document_identifier' => $documentIdentifier,
            'count' => count($keywordMatches),
            'items' => array_map($this->payloadFactory->itemSummary(...), $keywordMatches),
            'vector_error' => $vectorError,
        ];
    }

    /**
     * @param list<array{item: LsItem, relationCount: int, relations: list<array{type: string, displayType: string, direction: 'outgoing'|'incoming', associationId: ?int, associationIdentifier: string, sequenceNumber: ?int, notes: ?string}>}> $associationMatches
     * @param list<array{lsItem: LsItem, similarity: float|int, embedding: mixed}> $vectorMatches
     *
     * @return list<array{
     *     item: array<string, mixed>,
     *     association_count: int,
     *     association_types: list<string>,
     *     vector_similarity: ?float,
     *     reasons: list<string>
     * }>
     */
    private function combineRelatedMatches(array $associationMatches, array $vectorMatches, int $limit): array
    {
        /** @var array<string, array{
         *     item: LsItem,
         *     association_count: int,
         *     association_types: list<string>,
         *     vector_similarity: ?float
         * }> $combined
         */
        $combined = [];

        foreach ($associationMatches as $match) {
            $item = $match['item'];
            $key = $this->itemKey($item);

            $associationTypes = array_values(array_unique(array_map(
                static fn (array $relation): string => $relation['type'],
                $match['relations']
            )));

            $combined[$key] = [
                'item' => $item,
                'association_count' => $match['relationCount'],
                'association_types' => $associationTypes,
                'vector_similarity' => null,
            ];
        }

        foreach ($vectorMatches as $match) {
            $item = $match['lsItem'];
            $key = $this->itemKey($item);
            $similarity = (float) $match['similarity'];

            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'item' => $item,
                    'association_count' => 0,
                    'association_types' => [],
                    'vector_similarity' => $similarity,
                ];

                continue;
            }

            if (null === $combined[$key]['vector_similarity'] || $similarity > $combined[$key]['vector_similarity']) {
                $combined[$key]['vector_similarity'] = $similarity;
            }
        }

        $matches = array_values($combined);
        usort(
            $matches,
            static function (array $left, array $right): int {
                $leftSimilarity = $left['vector_similarity'] ?? -INF;
                $rightSimilarity = $right['vector_similarity'] ?? -INF;
                if ($leftSimilarity !== $rightSimilarity) {
                    return $rightSimilarity <=> $leftSimilarity;
                }

                if ($left['association_count'] !== $right['association_count']) {
                    return $right['association_count'] <=> $left['association_count'];
                }

                return $right['item']->getUpdatedAt()->getTimestamp() <=> $left['item']->getUpdatedAt()->getTimestamp();
            }
        );

        $matches = array_slice($matches, 0, $limit);

        return array_map(
            fn (array $match): array => [
                'item' => $this->payloadFactory->itemSummary($match['item']),
                'association_count' => $match['association_count'],
                'association_types' => $match['association_types'],
                'vector_similarity' => $match['vector_similarity'],
                'reasons' => array_values(array_filter([
                    $match['association_count'] > 0 ? 'association' : null,
                    null !== $match['vector_similarity'] ? 'vector' : null,
                ])),
            ],
            $matches
        );
    }

    /**
     * @param list<array{lsItem: LsItem, similarity: float|int, embedding: mixed}> $vectorMatches
     *
     * @return list<array{lsItem: LsItem, similarity: float|int, embedding: mixed}>
     */
    private function filterVectorMatches(array $vectorMatches, ?LsItem $sourceItem, int $limit): array
    {
        $sourceKey = null !== $sourceItem ? $this->itemKey($sourceItem) : null;

        $filtered = array_values(array_filter(
            $vectorMatches,
            function (array $match) use ($sourceKey): bool {
                $item = $match['lsItem'];

                if (!$this->queryService->isPublicDocument($item->getLsDoc())) {
                    return false;
                }

                if (null !== $sourceKey && $sourceKey === $this->itemKey($item)) {
                    return false;
                }

                return true;
            }
        ));

        return array_slice($filtered, 0, $limit);
    }

    private function itemKey(LsItem $item): string
    {
        return ($item->getLsDocIdentifier() ?? '').'::'.$item->getIdentifier();
    }

    private function normalizeLimit(int $limit, int $default, int $max): int
    {
        if ($limit <= 0) {
            return $default;
        }

        return min($limit, $max);
    }
}
