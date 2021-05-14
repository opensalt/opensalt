<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;

trait AssociationLinkTrait
{
    protected function createAssociationLinks(LsDoc|LsItem $object, array $context = []): ?array
    {
        if (null === ($context['case-json-ld'] ?? null)) {
            return null;
        }

        if (null !== ($context['no-association-links'] ?? null)) {
            return null;
        }

        $associationSet = [];

        $associations = $object->getAssociations();
        foreach ($associations as $association) {
            $link = $this->createAssociationLink($association, 'destination');
            if (null !== $link) {
                $associationSet[] = $link;
            }
        }

        $associations = $object->getInverseAssociations();
        foreach ($associations as $association) {
            $link = $this->createAssociationLink($association, 'origin');
            if (null !== $link) {
                $associationSet[] = $link;
            }
        }

        if (0 === count($associationSet)) {
            return null;
        }

        return $associationSet;
    }

    private function createAssociationLink(LsAssociation $association, string $which): ?array
    {
        if (!in_array($which, ['origin', 'destination'])) {
            throw new \InvalidArgumentException('Expecting "origin" or "destination" for which part of the association is wanted');
        }

        $target = match ($which) {
            'origin' => $association->getOrigin(),
            'destination' => $association->getDestination(),
            default => throw new \InvalidArgumentException('Expecting "origin" or "destination"')
        };

        if (null === $target) {
            return null;
        }

        if (is_object($target)) {
            $targetDoc = match (true) {
                $target instanceof LsDoc => $target,
                $target instanceof LsItem => $target->getLsDoc(),
            };

            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT === $targetDoc->getAdoptionStatus()) {
                return null;
            }
        }

        $targetLink = $this->api1Uris->getNodeLinkUri($which, $association);

        return [
            'type' => 'AssociationLink',
            'associationType' => $association->getNormalizedType(match ($which) {
                'destination' => $association->getType(),
                'origin' => LsAssociation::inverseName($association->getType()),
                default => throw new \InvalidArgumentException('Expecting "origin" or "destination"')
            }),
            'title' => $targetLink['title'],
            'identifier' => $targetLink['identifier'],
            'targetId' => $targetLink['uri'],
        ];
    }
}
