<?php

namespace App\Repository\Framework\Mirror;

use App\Entity\Framework\Mirror\OAuthCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class OAuthCredentialRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OAuthCredential::class);
    }
}
