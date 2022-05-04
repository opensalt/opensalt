<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterionLevel;
use Doctrine\Persistence\ManagerRegistry;

class CfRubricCriterionLevelRepository extends AbstractLsBaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CfRubricCriterionLevel::class);
    }
}
