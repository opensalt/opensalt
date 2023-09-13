<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

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
     * @return LsDefAssociationGrouping[]
     */
    public function findByIdentifiers(array $identifiers): array
    {
        return $this->createQueryBuilder('ag')
            ->select('ag')
            ->indexBy('ag', 'ag.identifier')
            ->where('ag.identifier IN (:identifiers)')
            ->setParameter('identifiers', $identifiers, ArrayParameterType::STRING)
            ->getQuery()
            ->getResult()
        ;
    }
}
