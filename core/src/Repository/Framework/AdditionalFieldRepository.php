<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\AdditionalField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdditionalField>
 */
class AdditionalFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdditionalField::class);
    }
}
