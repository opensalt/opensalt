<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Articulations\Model\ArticulationFrameworkResolution;
use App\Articulations\Model\EntityIdentifiers;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;

final readonly class ArticulationFrameworkResolver
{
    public function __construct(
        private EntityIdentifierResolver $identifierResolver,
        private LsAssociationRepository $associationRepository,
        private LsDocRepository $docRepository,
    ) {
    }

    public function resolve(EntityIdentifiers $sending, EntityIdentifiers $receiving): ?ArticulationFrameworkResolution
    {
        $sendingMatch = $this->identifierResolver->findSendingInstitutionMatch($sending);
        $receivingMatch = $this->identifierResolver->findReceivingInstitutionMatch($receiving);
        if (null === $sendingMatch || null === $receivingMatch) {
            return null;
        }

        $docIds = $this->associationRepository->findArticulationDocIdsForInstitutionItems(
            $sendingMatch->item,
            $receivingMatch->item,
        );
        if ([] === $docIds) {
            return null;
        }

        /** @var list<LsDoc> $docs */
        $docs = $this->docRepository->findBy(['id' => $docIds]);
        if ([] === $docs) {
            return null;
        }

        usort($docs, $this->compareCandidates(...));

        return new ArticulationFrameworkResolution($docs[0], $sendingMatch, $receivingMatch);
    }

    private function compareCandidates(LsDoc $a, LsDoc $b): int
    {
        $yearA = $this->yearTuple($this->academicYearCode($a));
        $yearB = $this->yearTuple($this->academicYearCode($b));

        $cmp = $yearB[0] <=> $yearA[0];
        if (0 !== $cmp) {
            return $cmp;
        }

        $cmp = $yearB[1] <=> $yearA[1];
        if (0 !== $cmp) {
            return $cmp;
        }

        return ($b->getId() ?? 0) <=> ($a->getId() ?? 0);
    }

    private function academicYearCode(LsDoc $doc): ?string
    {
        $year = $doc->getExtensions()['ais:academicYear'] ?? null;
        if (is_array($year) && isset($year['code'])) {
            return (string) $year['code'];
        }
        if (is_string($year)) {
            return $year;
        }

        return null;
    }

    /**
     * @return array{0:int,1:int} begin,end — missing year sorts as [0,0]
     */
    private function yearTuple(?string $code): array
    {
        if (null === $code || !preg_match('/^(\d{4})\s*-\s*(\d{4})$/', trim($code), $matches)) {
            return [0, 0];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }
}
