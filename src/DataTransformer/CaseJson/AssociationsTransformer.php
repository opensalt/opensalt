<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackageAssociation;
use App\DTO\CaseJson\Definitions;
use App\DTO\CaseJson\LinkGenURI;
use App\DTO\CaseJson\LinkURI;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use Doctrine\ORM\EntityManagerInterface;

class AssociationsTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Definitions
     */
    private $definitions;

    /**
     * @var LsItem[]
     */
    private $items;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param CFPackageAssociation[] $cfAssociations
     * @param LsItem[] $items
     * @param Definitions $definitions
     * @return LsAssociation[]
     */
    public function transform(array $cfAssociations, LsDoc $doc, array $items, Definitions $definitions): array
    {
        $this->definitions = $definitions;
        $this->items = $items;

        $associations = $this->findExistingAssociations($cfAssociations);

        foreach ($cfAssociations as $cfAssociation) {
            $association = $associations[$cfAssociation->identifier->toString()] ?? $this->createAssociation($cfAssociation, $doc);
            $associations[$cfAssociation->identifier->toString()] = $this->updateAssociation($association, $cfAssociation, $doc);
        }

        $this->removeUnknownAssociations($doc, array_keys($associations));

        return $associations;
    }

    /**
     * @param CFPackageAssociation[] $cfAssociations
     * @return LsAssociation[]
     */
    private function findExistingAssociations(array $cfAssociations): array
    {
        /** @var LsAssociationRepository $repo */
        $repo = $this->em->getRepository(LsAssociation::class);

        $newIds = array_map(static function (CFPackageAssociation $item) {
            return $item->identifier->toString();
        }, $cfAssociations);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param string[] $associationIdentifiers
     */
    private function removeUnknownAssociations(LsDoc $doc, array $associationIdentifiers): void
    {
        $docAssociations = $doc->getDocAssociations();

        foreach ($docAssociations as $association) {
            if (!in_array($association->getIdentifier(), $associationIdentifiers, true)) {
                $this->em->remove($association);
            }
        }
    }

    private function createAssociation(CFPackageAssociation $cfAssociation, LsDoc $doc): LsAssociation
    {
        $association = new LsAssociation($cfAssociation->identifier);
        $association->setLsDoc($doc);

        $this->em->persist($association);

        return $association;
    }

    private function updateAssociation(LsAssociation $association, CFPackageAssociation $cfAssociation, LsDoc $doc): LsAssociation
    {
        /** @noinspection NullPointerExceptionInspection */
        if ($association->getLsDoc()->getIdentifier() !== $doc->getIdentifier()) {
            throw new \UnexpectedValueException('Cannot change the document of an association');
        }

        $association->setUri($cfAssociation->uri);
        $this->setOrigin($association, $cfAssociation->originNodeURI, $doc);
        $association->setType($cfAssociation->associationType);
        $this->setDestination($association, $cfAssociation->destinationNodeURI, $doc);

        $association->setSequenceNumber($cfAssociation->sequenceNumber);
        $this->setGroup($association, $cfAssociation->cfAssociationGroupingURI);
        $association->setUpdatedAt($cfAssociation->lastChangeDateTime);

        return $association;
    }

    private function setOrigin(LsAssociation $association, LinkGenURI $originNodeURI, LsDoc $doc): LsAssociation
    {
        $identifier = $originNodeURI->identifier;
        $item = $this->items[$identifier] ?? null;
        if ($item) {
            $association = $association->setOrigin($item);

            return $association;
        }

        if ($doc->getIdentifier() === $identifier) {
            $association = $association->setOrigin($doc);

            return $association;
        }

        $item = $this->em->getRepository(LsItem::class)->findOneByIdentifier($identifier);
        if ($item) {
            $association = $association->setOrigin($item);

            return $association;
        }

        $otherDoc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($identifier);
        if ($otherDoc) {
            $association = $association->setOrigin($otherDoc);

            return $association;
        }

        $association->setOrigin($originNodeURI->uri, $identifier);

        return $association;
    }

    private function setDestination(LsAssociation $association, LinkGenURI $destinationNodeURI, LsDoc $doc): LsAssociation
    {
        $identifier = $destinationNodeURI->identifier;
        $item = $this->items[$identifier] ?? null;
        if ($item) {
            $association = $association->setDestination($item);

            return $association;
        }

        if ($doc->getIdentifier() === $identifier) {
            $association = $association->setDestination($doc);

            return $association;
        }

        $item = $this->em->getRepository(LsItem::class)->findOneByIdentifier($identifier);
        if ($item) {
            $association = $association->setDestination($item);

            return $association;
        }

        $otherDoc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($identifier);
        if ($otherDoc) {
            $association = $association->setDestination($otherDoc);

            return $association;
        }

        $association->setDestination($destinationNodeURI->uri, $identifier);

        return $association;
    }

    private function setGroup(LsAssociation $association, ?LinkURI $cfAssociationGroupingURI): LsAssociation
    {
        if (null === $cfAssociationGroupingURI) {
            $association->setGroup(null);
        }

        $identifier = $cfAssociationGroupingURI->identifier->toString();
        $group = $this->definitions->associationGroupings[$identifier] ?? null;
        if (null !== $group) {
            $association->setGroup($group);
        }

        return $association;
    }
}
