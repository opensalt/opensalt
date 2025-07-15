<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefLicence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LsDefLicence|null findOneByIdentifier(string $identifier)
 */
class LsDefLicenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefLicence::class);
    }

    /**
     * @return LsDefLicence[]
     */
    public function getList(): array
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
        if ([] === $identifiers) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));

        return $qb->getQuery()->getResult();
    }
}
