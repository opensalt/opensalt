<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AdditionalField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AdditionalField|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdditionalField|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdditionalField[]    findAll()
 * @method AdditionalField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdditionalFieldRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AdditionalField::class);
    }
}
