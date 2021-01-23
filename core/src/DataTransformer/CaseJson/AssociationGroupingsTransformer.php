<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFAssociationGrouping;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Repository\Framework\LsDefAssociationGroupingRepository;
use Doctrine\ORM\EntityManagerInterface;

class AssociationGroupingsTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param CFAssociationGrouping[] $cfAssociationGroupings
     *
     * @return LsDefAssociationGrouping[]
     */
    public function transform(array $cfAssociationGroupings): array
    {
        if (0 === count($cfAssociationGroupings)) {
            return [];
        }

        $existingGroups = $this->findExistingGroups($cfAssociationGroupings);

        foreach ($cfAssociationGroupings as $cfAssociationGrouping) {
            $this->updateAssociationGrouping($cfAssociationGrouping, $existingGroups);
        }

        return $existingGroups;
    }

    /**
     * @param CFAssociationGrouping[] $cfAssociationGroupings
     *
     * @return LsDefAssociationGrouping[]
     */
    protected function findExistingGroups(array $cfAssociationGroupings): array
    {
        /** @var LsDefAssociationGroupingRepository $repo */
        $repo = $this->em->getRepository(LsDefAssociationGrouping::class);

        $newIds = array_map(static function (CFAssociationGrouping $group) {
            return $group->identifier->toString();
        }, $cfAssociationGroupings);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefAssociationGrouping[] $existingAssociationGroups
     */
    protected function updateAssociationGrouping(CFAssociationGrouping $cfAssociationGrouping, array &$existingAssociationGroups): void
    {
        $grouping = $this->findOrCreateAssociationGrouping($cfAssociationGrouping, $existingAssociationGroups);
        $grouping->setUri($cfAssociationGrouping->uri);
        $grouping->setTitle($cfAssociationGrouping->title);
//        $grouping->setLsDoc($lsDoc); // TODO
        $grouping->setDescription($cfAssociationGrouping->description);
        $grouping->setChangedAt($cfAssociationGrouping->lastChangeDateTime);
    }

    /**
     * @param LsDefAssociationGrouping[] $existingAssociationGroups
     */
    protected function findOrCreateAssociationGrouping(CFAssociationGrouping $cfAssociationGrouping, array &$existingAssociationGroups): LsDefAssociationGrouping
    {
        if (!array_key_exists($cfAssociationGrouping->identifier->toString(), $existingAssociationGroups)) {
            $newGrouping = new LsDefAssociationGrouping($cfAssociationGrouping->identifier->toString());

            $this->em->persist($newGrouping);
            $existingAssociationGroups[$newGrouping->getIdentifier()] = $newGrouping;
        }

        return $existingAssociationGroups[$cfAssociationGrouping->identifier->toString()];
    }
}
