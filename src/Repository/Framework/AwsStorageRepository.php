<?php

namespace App\Repository\Framework;

use App\Entity\Framework\ImportLog;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ImportLogRepository
 */
class ImportLogRepository extends EntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ImportLog::class);
    }

}
