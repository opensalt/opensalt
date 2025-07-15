<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefConcept;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LsDefConcept|null findOneByIdentifier(string $identifier)
 */
class LsDefConceptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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
        if ([] === $identifiers) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));

        return $qb->getQuery()->getResult();
    }
}
