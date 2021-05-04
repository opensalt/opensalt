<?php

namespace App\Repository\Framework\Mirror;

use App\Entity\Framework\Mirror\OAuthCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OAuthCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthCredential::class);
    }
}
