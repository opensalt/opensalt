<?php

namespace App\Repository\Framework;

use App\Entity\Framework\FrameworkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method FrameworkType|null find($id, $lockMode = null, $lockVersion = null)
 * @method FrameworkType|null findOneBy(array $criteria, array $orderBy = null)
 * @method FrameworkType[]    findAll()
 * @method FrameworkType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FrameworkTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrameworkType::class);
    }

    /**
     * @return array|FrameworkType[]
     */
    public function getList()
    {
        $qBuilder = $this->createQueryBuilder('f', 'f.value')
            ->orderBy('f.value');

        return $qBuilder->getQuery()->getResult();
    }
}
