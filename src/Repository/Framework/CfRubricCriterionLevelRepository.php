<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterionLevel;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CfRubricCriterionLevelRepository extends AbstractLsBaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CfRubricCriterionLevel::class);
    }

}
