<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\AssociationSubtype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssociationSubtype>
 */
class AssociationSubtypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssociationSubtype::class);
    }
}
