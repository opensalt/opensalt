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

    /**
     * @param string[] $identifiers
     *
     * @return LsDefConcept[]
     */
    public function findByIdentifiers(array $identifiers): array
    {
        if (0 === count($identifiers)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));

        return $qb->getQuery()->getResult();
    }
}
