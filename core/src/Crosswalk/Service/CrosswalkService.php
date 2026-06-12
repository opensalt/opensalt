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
     * Find the best matching item in the destination framework for a given source item.
     *
     * @return array{lsItem: LsItem, similarity: float}|null
     */
    public function findBestMatch(
        LsItem $sourceItem,
        int $destinationFrameworkId,
        float $threshold,
    ): ?array {
        $results = $this->vectorSearchService->searchByLsItem(
            $sourceItem,
            1,
            $destinationFrameworkId,
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
}
