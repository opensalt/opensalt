<?php

declare(strict_types=1);

namespace App\Crosswalk\Service;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;

readonly class CrosswalkService
{
    public const RESULT_CREATED_EXACT = 'created_exact';
    public const RESULT_CREATED_RELATED = 'created_related';
    public const RESULT_SKIPPED_BELOW_THRESHOLD = 'skipped';
    public const RESULT_SKIPPED_DUPLICATE = 'skipped_duplicate';

    public function __construct(
        private VectorSearchService $vectorSearchService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function processItem(
        LsItem $sourceItem,
        LsItem $destItem,
        float $similarity,
        float $exactMatchThreshold,
        int $crosswalkDocId,
        float $threshold = 0.75,
        string $jobId = '',
    ): string {
        if ($similarity < $threshold) {
            return self::RESULT_SKIPPED_BELOW_THRESHOLD;
        }

        if ($this->associationExists($sourceItem, $destItem, $crosswalkDocId)) {
            return self::RESULT_SKIPPED_DUPLICATE;
        }

        $isExactMatch = $similarity >= $exactMatchThreshold;
        $associationType = $isExactMatch ? LsAssociation::EXACT_MATCH_OF : LsAssociation::RELATED_TO;
        $subtype = $isExactMatch ? 'exact' : 'related';

        $crosswalkDoc = $this->entityManager->getReference(LsDoc::class, $crosswalkDocId);

        $association = new LsAssociation();
        $association->setLsDoc($crosswalkDoc);
        $association->setOrigin($sourceItem);
        $association->setDestination($destItem);
        $association->setType($associationType);
        $association->setSubtype($subtype);
        $association->setExtensions([
            'crosswalk:confidence' => $similarity,
            'crosswalk:subtype' => $subtype,
            'crosswalk:status' => 'pending',
            'crosswalk:jobId' => $jobId,
        ]);

        $this->entityManager->persist($association);

        return $isExactMatch ? self::RESULT_CREATED_EXACT : self::RESULT_CREATED_RELATED;
    }

    /**
     * Check whether an association with the same origin/destination
     * already exists in the given crosswalk framework document.
     *
     * Uses a lightweight raw query (outside any ORM transaction) so it
     * does not hold locks or interfere with concurrent operations.
     */
    private function associationExists(LsItem $origin, LsItem $destination, int $crosswalkDocId): bool
    {
        $result = $this->entityManager->getConnection()->fetchOne(
            'SELECT 1 FROM ls_association
             WHERE ls_doc_id = :docId
               AND origin_lsitem_id = :originId
               AND destination_lsitem_id = :destId
             LIMIT 1',
            [
                'docId' => $crosswalkDocId,
                'originId' => $origin->getId(),
                'destId' => $destination->getId(),
            ],
        );

        return false !== $result;
    }

    /**
     * Find the best matching item in the destination framework for a given source item.
     *
     * @return array{lsItem: LsItem, similarity: float}|null
     */
    public function findBestMatch(
        LsItem $sourceItem,
        int $destinationFrameworkId,
        float $threshold,
        bool $leafOnly = false,
    ): ?array {
        $results = $this->vectorSearchService->searchByLsItem(
            $sourceItem,
            1,
            $destinationFrameworkId,
            $leafOnly,
        );

        if ([] === $results) {
            return null;
        }

        $best = $results[0];
        if ($best['similarity'] < $threshold) {
            return null;
        }

        return $best;
    }

    /**
     * Return the IDs of leaf items (items with no children) for a framework.
     *
     * @return list<int>
     */
    public function getLeafItemIds(int $frameworkId): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT li.id
             FROM ls_item li
             WHERE li.ls_doc_id = :frameworkId
               AND li.id NOT IN (
                   SELECT a.destination_lsitem_id
                   FROM ls_association a
                   WHERE a.type = :childOfType
                     AND a.destination_lsitem_id IS NOT NULL
               )',
            [
                'frameworkId' => $frameworkId,
                'childOfType' => LsAssociation::CHILD_OF,
            ],
        );

        return array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));
    }

    public function countLeafItems(int $frameworkId): int
    {
        return \count($this->getLeafItemIds($frameworkId));
    }
}
