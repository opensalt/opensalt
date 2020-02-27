<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefLicence;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LsDefLicence|null findOneByIdentifier(string $identifier)
 */
class LsDefLicenceRepository extends AbstractLsDefinitionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefLicence::class);
    }

    /**
     * @return array|LsDefLicence[]|ArrayCollection
     */
    public function getList()
    {
        $qBuilder = $this->createQueryBuilder('s', 's.title')
            ->orderBy('s.title');

        return $qBuilder->getQuery()->getResult();
    }

    /**
     * @param string[] $identifiers
     *
     * @return LsDefLicence[]
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
