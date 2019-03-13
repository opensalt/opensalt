<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LsDefAssociationGrouping|null findOneByIdentifier(string $identifier)
 */
class LsDefAssociationGroupingRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefAssociationGrouping::class);
    }
}
