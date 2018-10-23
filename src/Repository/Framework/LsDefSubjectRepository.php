<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefSubject;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LsDefSubjectRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefSubject::class);
    }

    /**
     * @return array|LsDefSubject[]|ArrayCollection
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
