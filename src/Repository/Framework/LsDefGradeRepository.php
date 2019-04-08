<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefGrade;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LsDefGradeRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefGrade::class);
    }
}
