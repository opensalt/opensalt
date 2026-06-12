<?php

declare(strict_types=1);

namespace App\Crosswalk\Repository;

use App\Crosswalk\Entity\CrosswalkJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrosswalkJob>
 */
class CrosswalkJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrosswalkJob::class);
    }

    public function save(CrosswalkJob $job, bool $flush = true): void
    {
        $this->getEntityManager()->persist($job);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return CrosswalkJob[]
     */
    public function findByCrosswalkFramework(int $frameworkId, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.crosswalkFrameworkId = :frameworkId')
            ->setParameter('frameworkId', $frameworkId)
            ->orderBy('j.queuedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByCrosswalkFramework(int $frameworkId): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.crosswalkFrameworkId = :frameworkId')
            ->setParameter('frameworkId', $frameworkId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
