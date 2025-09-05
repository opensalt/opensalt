<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterionLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CfRubricCriterionLevel>
 */
class CfRubricCriterionLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CfRubricCriterionLevel::class);
    }
}
