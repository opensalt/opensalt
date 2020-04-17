<?php

namespace App\Repository\Framework;

use App\Entity\Framework\ImportLog;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

/**
 * ImportLogRepository.
 */
class ImportLogRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportLog::class);
    }

}
