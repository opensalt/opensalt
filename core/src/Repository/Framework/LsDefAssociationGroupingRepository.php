<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LsDefAssociationGrouping|null findOneByIdentifier(string $identifier)
 * @method LsDefAssociationGrouping[] findByIdentifier(array $identifiers)
 */
class LsDefAssociationGroupingRepository extends AbstractLsDefinitionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefAssociationGrouping::class);
    }
}
