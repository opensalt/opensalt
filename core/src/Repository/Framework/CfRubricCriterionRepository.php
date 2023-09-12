<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterion;
use Doctrine\Persistence\ManagerRegistry;

class CfRubricCriterionRepository extends AbstractLsBaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CfRubricCriterion::class);
    }
}
