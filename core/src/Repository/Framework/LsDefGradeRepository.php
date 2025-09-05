<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LsDefGrade>
 */
class LsDefGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefGrade::class);
    }
}
