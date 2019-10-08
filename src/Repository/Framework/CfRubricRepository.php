<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubric;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CfRubricRepository extends AbstractLsBaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CfRubric::class);
    }

    /**
     * @param string[] $identifiers
     * @return CfRubric[]
     */
    public function findByIdentifiers(array $identifiers): array
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
