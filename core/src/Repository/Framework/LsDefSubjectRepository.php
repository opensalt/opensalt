<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefSubject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LsDefSubject|null findOneByIdentifier(string $identifier)
 *
 * @extends ServiceEntityRepository<LsDefSubject>
 */
class LsDefSubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsDefSubject::class);
    }

    /**
     * @return array|LsDefSubject[]
     */
    public function getList(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('s', 's.title')
            ->orderBy('s.title');

        if (null !== $search) {
            $qb->andWhere('s.title LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $identifiers
     *
     * @return LsDefSubject[]
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
