<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefGrade;
use Doctrine\Common\Persistence\ManagerRegistry;

class LsDefGradeRepository extends AbstractLsDefinitionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefGrade::class);
    }
}
