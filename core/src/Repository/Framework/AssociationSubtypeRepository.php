<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AssociationSubtype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssociationSubtype|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssociationSubtype|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssociationSubtype[]    findAll()
 * @method AssociationSubtype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssociationSubtypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssociationSubtype::class);
    }

    // /**
    //  * @return AssociationSubtype[] Returns an array of AssociationSubtype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AssociationSubtype
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
