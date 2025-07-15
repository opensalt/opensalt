<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CfRubricRepository extends ServiceEntityRepository
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
        if ([] === $identifiers) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));
        $qb->leftJoin('t.criteria', 'c');
        $qb->leftJoin('c.levels', 'l');

        return $qb->getQuery()->getResult();
    }
}
