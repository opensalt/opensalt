<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LsDefAssociationGrouping|null findOneByIdentifier(string $identifier)
 */
class LsDefAssociationGroupingRepository extends AbstractLsDefinitionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefAssociationGrouping::class);
    }

    /**
     * @param string[] $identifiers
     *
     * @return LsDefAssociationGrouping[]
     */
    public function findByIdentifiers(array $identifiers): array
    {
        if (0 === count($identifiers)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));

        return $qb->getQuery()->getResult();
    }
}
