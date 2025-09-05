<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CfRubricCriterion>
 */
class CfRubricCriterionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CfRubricCriterion::class);
    }
}
