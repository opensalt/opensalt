<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * LsDefAssociationGroupingRepository
 */
class LsDefAssociationGroupingRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefAssociationGrouping::class);
    }

}
