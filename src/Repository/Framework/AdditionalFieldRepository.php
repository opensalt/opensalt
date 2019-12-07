<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AdditionalField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AdditionalField|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdditionalField|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdditionalField[]    findAll()
 * @method AdditionalField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdditionalFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdditionalField::class);
    }
}
