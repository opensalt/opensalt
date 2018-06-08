<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefConcept;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * LsDefConceptRepository
 */
class LsDefConceptRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefConcept::class);
    }

}
