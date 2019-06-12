<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefConcept;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LsDefConcept|null findOneByIdentifier(string $identifier)
 */
class LsDefConceptRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefConcept::class);
    }
}
