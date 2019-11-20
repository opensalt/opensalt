<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFAssociationGrouping;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
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
    public function transform(array $cfAssociationGroupings, $doc=null): array
    {
        if (0 === count($cfAssociationGroupings)) {
            return [];
        }

        $existingGroups = $this->findExistingGroups($cfAssociationGroupings);
        if(!empty($doc)) {
            foreach ($cfAssociationGroupings as $cfAssociationGrouping) {
                $this->updateAssociationGrouping($cfAssociationGrouping, $existingGroups, $doc);
            }
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
     * @param CFAssociationGrouping $cfAssociationGrouping
     * @param LsDefAssociationGrouping[] $existingAssociationGroups
     */
    protected function updateAssociationGrouping(CFAssociationGrouping $cfAssociationGrouping, array &$existingAssociationGroups, $doc): void
    {
        $grouping = $this->findOrCreateAssociationGrouping($cfAssociationGrouping, $existingAssociationGroups, $doc);
        $grouping->setUri($cfAssociationGrouping->uri);
        $grouping->setTitle($cfAssociationGrouping->title);
        if(!empty($doc)) {
            $grouping->setLsDoc($doc); // TODO
        }
        $grouping->setDescription($cfAssociationGrouping->description);
        $grouping->setChangedAt($cfAssociationGrouping->lastChangeDateTime);
    }

    /**
     * @param CFAssociationGrouping $cfAssociationGrouping
     * @param LsDefAssociationGrouping[] $existingAssociationGroups
     *
     * @return LsDefAssociationGrouping
     */
    protected function findOrCreateAssociationGrouping(CFAssociationGrouping $cfAssociationGrouping, array &$existingAssociationGroups, LsDoc $doc): LsDefAssociationGrouping
    {
        if (!array_key_exists($cfAssociationGrouping->identifier->toString(), $existingAssociationGroups)) {
            $repo = $this->em->getRepository(LsDoc::class);

            $newGrouping = new LsDefAssociationGrouping($cfAssociationGrouping->identifier->toString());
            $newGrouping->setTitle($cfAssociationGrouping->title);
            $newGrouping->setLsDoc($doc);

            $this->em->persist($newGrouping);
            $existingAssociationGroups[$newGrouping->getIdentifier()] = $newGrouping;
        }

        return $existingAssociationGroups[$cfAssociationGrouping->identifier->toString()];
    }
}
