<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use App\VectorSearch\Service\VectorSearchService;
use Mcp\Capability\Attribute\McpResource;

readonly class OpenSaltMcpResources
{
    public function __construct(
        private OpenSaltMcpQueryService $queryService,
        private OpenSaltMcpPayloadFactory $payloadFactory,
        private VectorSearchService $vectorSearchService,
        private int $paginationLimit = 50,
    ) {
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResource(uri: 'opensalt://documents', name: 'opensalt-documents')]
    public function documents(): array
    {
        $documents = $this->queryService->listPublicDocuments(limit: $this->paginationLimit);

        return $this->jsonResource(
            'opensalt://documents',
            [
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
                'count' => count($documents),
                'documents' => array_map($this->payloadFactory->documentSummary(...), $documents),
            ]
        );
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResource(uri: 'opensalt://items/recent', name: 'opensalt-items-recent')]
    public function recentItems(): array
    {
        $items = $this->queryService->listPublicItems(limit: $this->paginationLimit);

        return $this->jsonResource(
            'opensalt://items/recent',
            [
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
                'count' => count($items),
                'items' => array_map($this->payloadFactory->itemSummary(...), $items),
            ]
        );
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResource(uri: 'opensalt://vector-search/stats', name: 'opensalt-vector-search-stats')]
    public function vectorSearchStats(): array
    {
        return $this->jsonResource(
            'opensalt://vector-search/stats',
            [
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
                'vector_count' => $this->vectorSearchService->getVectorCount(),
            ]
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{uri: string, mimeType: string, text: string}
     */
    private function jsonResource(string $uri, array $payload): array
    {
        return [
            'uri' => $uri,
            'mimeType' => 'application/json',
            'text' => json_encode($payload, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
        ];
    }
}
