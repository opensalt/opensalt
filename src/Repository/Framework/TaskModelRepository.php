<?php

namespace App\Repository\Framework;

use App\Entity\Framework\TaskModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TaskModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskModel[]    findAll()
 * @method TaskModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskModelRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TaskModel::class);
    }

//    /**
//     * @return TaskModel[] Returns an array of TaskModel objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TaskModel
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
