<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefSubject;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LsDefSubject|null findOneByIdentifier(string $identifier)
 */
class LsDefSubjectRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
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
                ->setParameter('search', "%$search%");
        }

        return $qb->getQuery()->getResult();
    }
}
