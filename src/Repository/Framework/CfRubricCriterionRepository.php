<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubricCriterion;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CfRubricCriterionRepository extends AbstractLsBaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CfRubricCriterion::class);
    }

}
