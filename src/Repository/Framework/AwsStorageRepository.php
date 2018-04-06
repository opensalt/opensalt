<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AwsStorage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ImportLogRepository
 */
class AwsStorageRepository extends EntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AwsStorage::class);
    }

}
