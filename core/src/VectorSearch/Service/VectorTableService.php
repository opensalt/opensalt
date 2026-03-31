<?php

declare(strict_types=1);

namespace App\VectorSearch\Service;

use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Store\VectorStoreInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for managing vector storage and search inside the vector-search module.
 */
readonly class VectorTableService implements VectorStoreInterface
{
    private const TABLE_NAME = 'ls_item_embedding';
    private const TEMP_TABLE_NAME = 'tmp_ls_item_embedding_stage';
    private const EMBEDDING_DIMENSION = 384;
    private const MIN_CANDIDATES = 100;
    private const CANDIDATE_MULTIPLIER = 10;
    private const SEGMENT_COUNT = 12;
    private const SEGMENT_SIZE = 4; // bytes per segment (32 bits)
    private const MAX_SEGMENT_RADIUS = 2;

    /** @var list<int> Byte-level popcount lookup table (0-255 mapped to number of set bits) */
    private const BYTE_POPCOUNT = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        4, 5, 5, 6, 5, 6, 6, 7, 5, 6, 6, 7, 6, 7, 7, 8,
    ];

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.default_connection')]
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<float> $vector
     */
    public function storeEmbeddingVector(LsItemEmbedding $embedding, array $vector): void
    {
        $payload = $this->buildVectorPayload($vector);

        $embedding->setVectorData(
            $payload['vector'],
            $payload['normalizedVector'],
            $payload['magnitude'],
            $payload['binaryCode']
        );

        $this->logger->debug('Embedding vector stored', [
            'embedding_id' => $embedding->getId(),
        ]);
    }

    public function storeEmbeddingMetadata(LsItemEmbedding $embedding, bool $indexed): void
    {
        $embedding
            ->clearStoredVectorData()
            ->markIndexed($indexed);
    }

    /**
     * @param list<float> $vector
     */
    public function finalizeStoredEmbedding(LsItemEmbedding $embedding, array $vector): void
    {
        // MySQL-backed storage is persisted by Doctrine flush; no secondary sync is needed.
    }

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
    public function storeEmbeddingBatch(array $rows): int
    {
        if ([] === $rows) {
            return 0;
        }

        $this->createTemporaryStageTable();
        $this->connection->executeStatement(sprintf('TRUNCATE TABLE %s', self::TEMP_TABLE_NAME));

        $now = new \DateTimeImmutable();

        foreach ($rows as $row) {
            $payload = $this->buildVectorPayload($row['vector']);

            $this->connection->insert(self::TEMP_TABLE_NAME, [
                'ls_item_id' => $row['lsItemId'],
                'text' => $row['text'],
                'vector' => json_encode($payload['vector'], JSON_THROW_ON_ERROR),
                'normalized_vector' => json_encode($payload['normalizedVector'], JSON_THROW_ON_ERROR),
                'magnitude' => $payload['magnitude'],
                'binary_code' => $payload['binaryCode'],
                'is_leaf_node' => $row['isLeafNode'] ? 1 : 0,
                'source_hierarchy_updated_at' => $row['sourceHierarchyUpdatedAt']->format('Y-m-d H:i:s'),
                'is_indexed' => 1,
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        }

        $newlyCountedRows = (int) $this->connection->fetchOne(sprintf(
            <<<'SQL'
                SELECT COUNT(*)
                FROM %s stage
                LEFT JOIN %s embedding
                    ON embedding.ls_item_id = stage.ls_item_id
                   AND (
                        embedding.is_indexed = 1
                        OR (
                            embedding.vector IS NOT NULL
                            AND embedding.normalized_vector IS NOT NULL
                            AND embedding.magnitude IS NOT NULL
                            AND embedding.binary_code IS NOT NULL
                        )
                   )
                WHERE embedding.ls_item_id IS NULL
            SQL,
            self::TEMP_TABLE_NAME,
            self::TABLE_NAME
        ));

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(sprintf(
                <<<'SQL'
                    INSERT INTO %s (
                        ls_item_id,
                        text,
                        vector,
                        normalized_vector,
                        magnitude,
                        binary_code,
                        is_leaf_node,
                        source_hierarchy_updated_at,
                        is_indexed,
                        created_at,
                        updated_at
                    )
                    SELECT
                        ls_item_id,
                        text,
                        vector,
                        normalized_vector,
                        magnitude,
                        binary_code,
                        is_leaf_node,
                        source_hierarchy_updated_at,
                        is_indexed,
                        created_at,
                        updated_at
                    FROM %s
                    ON DUPLICATE KEY UPDATE
                        text = VALUES(text),
                        vector = VALUES(vector),
                        normalized_vector = VALUES(normalized_vector),
                        magnitude = VALUES(magnitude),
                        binary_code = VALUES(binary_code),
                        is_leaf_node = VALUES(is_leaf_node),
                        source_hierarchy_updated_at = VALUES(source_hierarchy_updated_at),
                        is_indexed = VALUES(is_indexed),
                        updated_at = VALUES(updated_at)
                SQL,
                self::TABLE_NAME,
                self::TEMP_TABLE_NAME
            ));

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $this->logger->debug('Embedding batch stored through temporary table', [
            'row_count' => count($rows),
            'newly_counted_rows' => $newlyCountedRows,
        ]);

        return $newlyCountedRows;
    }

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
    public function storeEmbeddingMetadataBatch(array $rows, bool $indexed): int
    {
        if ([] === $rows) {
            return 0;
        }

        $this->createTemporaryStageTable();
        $this->connection->executeStatement(sprintf('TRUNCATE TABLE %s', self::TEMP_TABLE_NAME));

        $now = new \DateTimeImmutable();

        foreach ($rows as $row) {
            $this->connection->insert(self::TEMP_TABLE_NAME, [
                'ls_item_id' => $row['lsItemId'],
                'text' => $row['text'],
                'vector' => null,
                'normalized_vector' => null,
                'magnitude' => null,
                'binary_code' => null,
                'is_leaf_node' => $row['isLeafNode'] ? 1 : 0,
                'source_hierarchy_updated_at' => $row['sourceHierarchyUpdatedAt']->format('Y-m-d H:i:s'),
                'is_indexed' => $indexed ? 1 : 0,
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        }

        $newlyCountedRows = (int) $this->connection->fetchOne(sprintf(
            <<<'SQL'
                SELECT COUNT(*)
                FROM %s stage
                LEFT JOIN %s embedding
                    ON embedding.ls_item_id = stage.ls_item_id
                   AND (
                        embedding.is_indexed = 1
                        OR (
                            embedding.vector IS NOT NULL
                            AND embedding.normalized_vector IS NOT NULL
                            AND embedding.magnitude IS NOT NULL
                            AND embedding.binary_code IS NOT NULL
                        )
                   )
                WHERE embedding.ls_item_id IS NULL
            SQL,
            self::TEMP_TABLE_NAME,
            self::TABLE_NAME
        ));

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(sprintf(
                <<<'SQL'
                    INSERT INTO %s (
                        ls_item_id,
                        text,
                        vector,
                        normalized_vector,
                        magnitude,
                        binary_code,
                        is_leaf_node,
                        source_hierarchy_updated_at,
                        is_indexed,
                        created_at,
                        updated_at
                    )
                    SELECT
                        ls_item_id,
                        text,
                        NULL,
                        NULL,
                        NULL,
                        NULL,
                        is_leaf_node,
                        source_hierarchy_updated_at,
                        is_indexed,
                        created_at,
                        updated_at
                    FROM %s
                    ON DUPLICATE KEY UPDATE
                        text = VALUES(text),
                        vector = NULL,
                        normalized_vector = NULL,
                        magnitude = NULL,
                        binary_code = NULL,
                        is_leaf_node = VALUES(is_leaf_node),
                        source_hierarchy_updated_at = VALUES(source_hierarchy_updated_at),
                        is_indexed = VALUES(is_indexed),
                        updated_at = VALUES(updated_at)
                SQL,
                self::TABLE_NAME,
                self::TEMP_TABLE_NAME
            ));

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $newlyCountedRows;
    }

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
    ): array {
        if ($limit < 1) {
            return [];
        }

        $this->assertExpectedDimension($queryVector);

        $normalizedQueryVector = $this->normalizeVector($queryVector);
        $binaryCode = $this->vectorToHex($normalizedQueryVector);
        $candidateLimit = max(self::MIN_CANDIDATES, $limit * self::CANDIDATE_MULTIPLIER);

        // Convert hex binary code to raw bytes for segment splitting
        $binaryBytes = hex2bin($binaryCode);
        if (false === $binaryBytes) {
            throw new \RuntimeException('Failed to decode binary code for segment splitting.');
        }

        $querySegments = $this->splitBinaryCodeIntoSegments($binaryBytes);

        // Per-segment query limit: fetch enough rows per segment without over-fetching
        $perSegmentLimit = min($candidateLimit * 5, 1000);

        // Iteratively increase segment radius until enough candidates are found
        /** @var array<int, array{ls_item_id: int|string, normalized_vector: string, binary_code: string}> $candidates */
        $candidates = [];
        for ($radius = 0; $radius <= self::MAX_SEGMENT_RADIUS; ++$radius) {
            for ($segIndex = 0; $segIndex < self::SEGMENT_COUNT; ++$segIndex) {
                $neighbors = $this->enumerateHammingNeighbors($querySegments[$segIndex], $radius);
                $rows = $this->probeSegment($segIndex, $neighbors, $frameworkId, $leafOnly, $perSegmentLimit, $kind);

                foreach ($rows as $row) {
                    $id = (int) $row['id'];
                    // Deduplicate by embedding ID — first occurrence wins (smaller radius)
                    if (!isset($candidates[$id])) {
                        $candidates[$id] = $row;
                    }
                }
            }

            $this->logger->debug('Segment probe pass completed', [
                'segment_radius' => $radius,
                'unique_candidate_count' => count($candidates),
                'candidate_limit' => $candidateLimit,
            ]);

            if (count($candidates) >= $candidateLimit) {
                break;
            }
        }

        // Compute Hamming distance in PHP for each unique candidate and sort
        $scoredCandidates = [];
        foreach ($candidates as $id => $candidate) {
            $candidateBinaryCode = $candidate['binary_code'];
            $hammingDist = $this->computeHammingDistance($binaryBytes, $candidateBinaryCode);
            $scoredCandidates[] = [
                'ls_item_id' => (int) $candidate['ls_item_id'],
                'normalized_vector' => $candidate['normalized_vector'],
                'hamming_dist' => $hammingDist,
            ];
        }

        usort(
            $scoredCandidates,
            static fn (array $left, array $right): int => $left['hamming_dist'] <=> $right['hamming_dist']
                ?: $left['ls_item_id'] <=> $right['ls_item_id']
        );

        // Take top candidates up to candidateLimit
        $scoredCandidates = array_slice($scoredCandidates, 0, $candidateLimit);

        // Rerank by cosine similarity
        $results = [];
        foreach ($scoredCandidates as $candidate) {
            $normalizedVector = $this->decodeVector($candidate['normalized_vector'] ?? null);
            if (null === $normalizedVector) {
                continue;
            }

            $results[] = [
                'lsItemId' => (int) $candidate['ls_item_id'],
                'similarity' => $this->dotProduct($normalizedQueryVector, $normalizedVector),
            ];
        }

        usort(
            $results,
            static fn (array $left, array $right): int => $right['similarity'] <=> $left['similarity']
                ?: $left['lsItemId'] <=> $right['lsItemId']
        );

        $results = array_slice($results, 0, $limit);

        $this->logger->debug('Vector search completed', [
            'limit' => $limit,
            'framework_id' => $frameworkId,
            'leaf_only' => $leafOnly,
            'candidate_count' => count($candidates),
            'results_count' => count($results),
        ]);

        return $results;
    }

    /**
     * @param list<float> $vector1
     * @param list<float> $vector2
     * @return float Cosine similarity (-1 to 1)
     */
    public function cosineSimilarity(array $vector1, array $vector2): float
    {
        $this->assertExpectedDimension($vector1);
        $this->assertExpectedDimension($vector2);

        return $this->dotProduct(
            $this->normalizeVector($vector1),
            $this->normalizeVector($vector2)
        );
    }

    /**
     * Get vector count.
     *
     * @return int Number of vectors in the table
     */
    public function getVectorCount(): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE is_indexed = 1 OR (normalized_vector IS NOT NULL AND binary_code IS NOT NULL)',
            self::TABLE_NAME
        );

        return (int) $this->connection->fetchOne($sql);
    }

    public function getVectorByLsItemId(int $lsItemId): ?array
    {
        $value = $this->connection->fetchOne(
            sprintf('SELECT normalized_vector FROM %s WHERE ls_item_id = :lsItemId', self::TABLE_NAME),
            [
                'lsItemId' => $lsItemId,
            ]
        );

        return $this->decodeVector($value);
    }

    public function getApproximateVectorCount(): int
    {
        $tableRows = $this->connection->fetchOne(
            <<<'SQL'
                SELECT TABLE_ROWS
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :tableName
            SQL,
            [
                'tableName' => self::TABLE_NAME,
            ]
        );

        return false !== $tableRows && null !== $tableRows ? (int) $tableRows : 0;
    }

    public function deleteByLsItemId(int $lsItemId): void
    {
        // Doctrine entity deletion removes the row for the MySQL-backed implementation.
    }

    /**
     * Compute the Hamming distance (number of differing bits) between two binary strings.
     */
    private function computeHammingDistance(string $a, string $b): int
    {
        $dist = 0;
        $len = strlen($a);
        for ($i = 0; $i < $len; ++$i) {
            $dist += self::BYTE_POPCOUNT[ord($a[$i]) ^ ord($b[$i])];
        }

        return $dist;
    }

    /**
     * Execute a single per-segment probe query using its dedicated B-tree index.
     *
     * @param list<int> $segmentValues
     * @return list<array{id: int|string, ls_item_id: int|string, normalized_vector: string|null, binary_code: string|null}>
     */
    private function probeSegment(
        int $segIndex,
        array $segmentValues,
        ?int $frameworkId,
        bool $leafOnly,
        int $limit,
        ?int $kind = null,
    ): array {
        $segColumn = sprintf('bc_seg_%d', $segIndex);

        $params = [
            'seg_values' => $segmentValues,
        ];
        $types = [
            'seg_values' => ArrayParameterType::INTEGER,
        ];

        $conditions = [
            'embedding.normalized_vector IS NOT NULL',
            'embedding.binary_code IS NOT NULL',
            sprintf('embedding.%s IN (:seg_values)', $segColumn),
        ];

        if (null !== $frameworkId) {
            $conditions[] = 'item.ls_doc_id = :frameworkId';
            $params['frameworkId'] = $frameworkId;
        }

        if ($leafOnly) {
            $conditions[] = 'embedding.is_leaf_node = :leafOnly';
            $params['leafOnly'] = true;
        }

        if (null !== $kind) {
            $conditions[] = 'item.discriminator = :kind';
            $params['kind'] = $kind;
        }

        $sql = sprintf(
            'SELECT embedding.id, embedding.ls_item_id, embedding.normalized_vector, embedding.binary_code'
            . ' FROM %s embedding'
            . ' INNER JOIN ls_item item ON item.id = embedding.ls_item_id'
            . ' WHERE %s'
            . ' LIMIT %d',
            self::TABLE_NAME,
            implode(' AND ', $conditions),
            $limit
        );

        return $this->connection->fetchAllAssociative($sql, $params, $types);
    }

    /**
     * Split a 48-byte binary string into 12 segments of 32-bit unsigned integers.
     *
     * @return list<int>
     */
    private function splitBinaryCodeIntoSegments(string $binaryBytes): array
    {
        $expectedLength = self::SEGMENT_COUNT * self::SEGMENT_SIZE;
        if (strlen($binaryBytes) !== $expectedLength) {
            throw new \InvalidArgumentException(sprintf('Expected %d bytes for segment splitting, got %d.', $expectedLength, strlen($binaryBytes)));
        }

        $segments = [];
        for ($i = 0; $i < self::SEGMENT_COUNT; ++$i) {
            $segmentBytes = substr($binaryBytes, $i * self::SEGMENT_SIZE, self::SEGMENT_SIZE);
            // Unpack as unsigned 32-bit big-endian integer
            $unpacked = unpack('N', $segmentBytes);
            $segments[] = $unpacked[1];
        }

        return $segments;
    }

    /**
     * Enumerate all 32-bit values within Hamming distance $radius of $value.
     *
     * For radius 0: returns [$value] (1 value)
     * For radius 1: returns $value plus all 1-bit flips (33 values)
     * For radius 2: adds all 2-bit flips (529 values total)
     *
     * @return list<int>
     */
    private function enumerateHammingNeighbors(int $value, int $radius): array
    {
        $neighbors = [$value];

        if (0 === $radius) {
            return $neighbors;
        }

        // Radius 1: flip each individual bit (32 values)
        for ($bit = 0; $bit < 32; ++$bit) {
            $neighbors[] = $value ^ (1 << $bit);
        }

        if (1 === $radius) {
            return $neighbors;
        }

        // Radius 2: flip every pair of bits (32*31/2 = 496 values)
        for ($bit1 = 0; $bit1 < 32; ++$bit1) {
            for ($bit2 = $bit1 + 1; $bit2 < 32; ++$bit2) {
                $neighbors[] = $value ^ ((1 << $bit1) | (1 << $bit2));
            }
        }

        return $neighbors;
    }

    /**
     * @param list<float> $vector
     */
    private function assertExpectedDimension(array $vector): void
    {
        if (self::EMBEDDING_DIMENSION !== count($vector)) {
            throw new \InvalidArgumentException(sprintf('Expected %d-dimensional vectors, got %d values.', self::EMBEDDING_DIMENSION, count($vector)));
        }
    }

    /**
     * @param list<float> $vector
     */
    private function getMagnitude(array $vector): float
    {
        $sum = 0.0;
        foreach ($vector as $value) {
            $sum += $value * $value;
        }

        return sqrt($sum);
    }

    /**
     * @param list<float> $vector
     * @return list<float>
     */
    private function normalizeVector(array $vector, ?float $magnitude = null): array
    {
        $magnitude ??= $this->getMagnitude($vector);
        if (0.0 == $magnitude) {
            $magnitude = 1e-10;
        }

        foreach ($vector as $index => $value) {
            $vector[$index] = $value / $magnitude;
        }

        return $vector;
    }

    /**
     * @param list<float> $vector
     */
    private function vectorToHex(array $vector): string
    {
        $binary = '';
        foreach ($vector as $value) {
            $binary .= $value > 0 ? '1' : '0';
        }

        $padded = str_pad($binary, (int) ceil(strlen($binary) / 8) * 8, '0', STR_PAD_LEFT);
        $hex = '';
        foreach (str_split($padded, 4) as $chunk) {
            $hex .= strtoupper(dechex(bindec($chunk)));
        }

        return str_pad($hex, (int) ceil(strlen($hex) / 4) * 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param list<float> $left
     * @param list<float> $right
     */
    private function dotProduct(array $left, array $right): float
    {
        $product = 0.0;

        foreach ($left as $index => $value) {
            $product += $value * ($right[$index] ?? 0.0);
        }

        return $product;
    }

    /**
     * @return list<float>|null
     */
    private function decodeVector(mixed $value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param list<float> $vector
     * @return array{
     *   vector: list<float>,
     *   normalizedVector: list<float>,
     *   magnitude: float,
     *   binaryCode: string
     * }
     */
    private function buildVectorPayload(array $vector): array
    {
        $this->assertExpectedDimension($vector);

        $magnitude = $this->getMagnitude($vector);
        $normalizedVector = $this->normalizeVector($vector, $magnitude);
        $binaryCode = hex2bin($this->vectorToHex($normalizedVector));

        if (false === $binaryCode) {
            throw new \RuntimeException('Failed to encode vector binary code.');
        }

        return [
            'vector' => $vector,
            'normalizedVector' => $normalizedVector,
            'magnitude' => $magnitude,
            'binaryCode' => $binaryCode,
        ];
    }

    private function createTemporaryStageTable(): void
    {
        $this->connection->executeStatement(sprintf(
            <<<'SQL'
                CREATE TEMPORARY TABLE IF NOT EXISTS %s (
                    ls_item_id INT NOT NULL PRIMARY KEY,
                    text LONGTEXT DEFAULT NULL,
                    vector JSON DEFAULT NULL,
                    normalized_vector JSON DEFAULT NULL,
                    magnitude DOUBLE PRECISION DEFAULT NULL,
                    binary_code BINARY(48) DEFAULT NULL,
                    is_leaf_node TINYINT(1) NOT NULL DEFAULT 0,
                    source_hierarchy_updated_at DATETIME DEFAULT NULL,
                    is_indexed TINYINT(1) NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE = InnoDB
            SQL,
            self::TEMP_TABLE_NAME
        ));
    }
}
