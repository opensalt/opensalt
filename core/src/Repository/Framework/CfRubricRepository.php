<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubric;
use Doctrine\Persistence\ManagerRegistry;

class CfRubricRepository extends AbstractLsBaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CfRubric::class);
    }

    /**
     * @param string[] $identifiers
     *
     * @return CfRubric[]
     */
    public function findByIdentifier(array $identifiers): array
    {
        if (0 === count($identifiers)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));
        $qb->leftJoin('t.criteria', 'c');
        $qb->leftJoin('c.levels', 'l');

        return $qb->getQuery()->getResult();
    }
}
