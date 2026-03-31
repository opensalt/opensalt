<?php

declare(strict_types=1);

namespace App\VectorSearch\Store;

use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Service\VectorTableService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ConfiguredVectorStore implements VectorStoreInterface
{
    public function __construct(
        private VectorTableService $mySqlVectorStore,
        private QdrantVectorStore $qdrantVectorStore,
        #[Autowire('%env(string:VECTOR_SEARCH_BACKEND)%')]
        private string $backend,
    ) {
    }

    /**
     * @param list<float> $vector
     */
    public function storeEmbeddingVector(LsItemEmbedding $embedding, array $vector): void
    {
        $this->getActiveStore()->storeEmbeddingVector($embedding, $vector);
    }

    /**
     * @param list<float> $vector
     */
    public function finalizeStoredEmbedding(LsItemEmbedding $embedding, array $vector): void
    {
        $this->getActiveStore()->finalizeStoredEmbedding($embedding, $vector);
    }

    public function storeEmbeddingBatch(array $rows): int
    {
        return $this->getActiveStore()->storeEmbeddingBatch($rows);
    }

    public function search(
        array $queryVector,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        return $this->getActiveStore()->search($queryVector, $limit, $frameworkId, $leafOnly, $kind);
    }

    public function searchFullText(
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
    ): array {
        return $this->getActiveStore()->searchFullText($queryText, $limit, $frameworkId, $leafOnly, $kind);
    }

    public function searchHybrid(
        array $queryVector,
        string $queryText,
        int $limit = 10,
        ?int $frameworkId = null,
        bool $leafOnly = false,
        ?int $kind = null,
        int $prefetchLimit = 20,
    ): array {
        return $this->getActiveStore()->searchHybrid($queryVector, $queryText, $limit, $frameworkId, $leafOnly, $kind, $prefetchLimit);
    }

    public function getVectorByLsItemId(int $lsItemId): ?array
    {
        return $this->getActiveStore()->getVectorByLsItemId($lsItemId);
    }

    public function deleteByLsItemId(int $lsItemId): void
    {
        $this->getActiveStore()->deleteByLsItemId($lsItemId);
    }

    public function getVectorCount(): int
    {
        return $this->getActiveStore()->getVectorCount();
    }

    private function getActiveStore(): VectorStoreInterface
    {
        return match (strtolower(trim($this->backend))) {
            'qdrant' => $this->qdrantVectorStore,
            'mysql', '' => $this->mySqlVectorStore,
            default => throw new \InvalidArgumentException(sprintf('Unsupported vector search backend "%s". Expected "mysql" or "qdrant".', $this->backend)),
        };
    }
}
