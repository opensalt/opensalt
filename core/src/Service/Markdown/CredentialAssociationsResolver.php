<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;

final readonly class CredentialAssociationsResolver
{
    public function __construct(
        private LsAssociationRepository $associationRepository,
        private LsItemRepository $itemRepository,
    ) {
    }

    /**
     * Criteria are items which precede the credential, alignments are all
     * other associated items.  Criteria found in alignments are removed from
     * the alignments.
     *
     * @return array{criteria: array<string,LsItem>, alignments: array<string,LsItem>}
     */
    public function resolve(LsItem $obj): array
    {
        $allAssociations = $this->associationRepository->findAllAssociationsForAsSplitArray($obj->getIdentifier());
        $criteria = [];
        $alignments = [];

        foreach ($allAssociations['associations'] as $association) {
            $destination = $association->getDestination();

            if ($destination instanceof LsDoc) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::PRECEDES:
                    break;

                default:
                    if ($destination instanceof LsItem) {
                        $alignments[$destination->getIdentifier()] = $destination;
                    }
                    break;
            }
        }

        foreach ($allAssociations['inverseAssociations'] as $association) {
            $origin = $association->getOrigin();
            if (is_string($origin)) {
                $origin = $this->itemRepository->findOneBy(['identifier' => $association->getOriginNodeIdentifier()]);
            }
            if (!$origin instanceof LsItem) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::EXEMPLAR:
                case LsAssociation::EXACT_MATCH_OF:
                    break;

                case LsAssociation::PRECEDES:
                    $criteria[$origin->getIdentifier()] = $origin;
                    break;

                default:
                    $alignments[$origin->getIdentifier()] = $origin;
                    break;
            }
        }

        foreach (array_keys($criteria) as $key) {
            if (array_key_exists($key, $alignments)) {
                unset($alignments[$key]);
            }
        }

        return ['criteria' => $criteria, 'alignments' => $alignments];
    }

    /** @return array<string> image candidate URIs found in exemplar associations */
    public function getExemplarImages(LsItem $obj): array
    {
        $allAssociations = $this->associationRepository->findAllAssociationsForAsSplitArray($obj->getIdentifier());
        $images = [];
        foreach ($allAssociations['associations'] as $association) {
            if (LsAssociation::EXEMPLAR === $association->getType()
                && is_string($association->getDestination())
                && (str_ends_with($association->getDestination(), '.png') || str_ends_with($association->getDestination(), '.svg'))
            ) {
                $images[] = $association->getDestination();
            }
        }

        return $images;
    }
}
