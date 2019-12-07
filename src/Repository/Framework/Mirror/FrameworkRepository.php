<?php

namespace App\Repository\Framework\Mirror;

use App\Entity\Framework\Mirror\Framework;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class FrameworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Framework::class);
    }

    public function findNext(): ?Framework
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.nextCheck < :now')
            ->andWhere('f.nextCheck IS NOT NULL')
            ->andWhere('f.include = 1')
            ->addOrderBy('f.priority', 'DESC')
            ->addOrderBy('f.lastCheck', 'ASC')
            ->getQuery()
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function markAsProcessing(Framework $framework): bool
    {
        $ret = $this->getEntityManager()->createQuery(<<<xENDx
                UPDATE {$this->getEntityName()} f
                   SET f.nextCheck = :nextCheck,
                       f.status = :status
                 WHERE f.id = :id
                   AND f.nextCheck = :checkAt
            xENDx)
            ->setParameter('nextCheck', new \DateTimeImmutable('now + 1 hour'))
            ->setParameter('status', Framework::STATUS_PROCESSING)
            ->setParameter('id', $framework->getId())
            ->setParameter('checkAt', $framework->getNextCheck())
            ->execute()
        ;

        return 1 === $ret;
    }
}
