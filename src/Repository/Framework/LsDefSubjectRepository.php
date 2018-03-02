<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefSubject;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * LsDefSubjectRepository
 */
class LsDefSubjectRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefSubject::class);
    }

    /**
     * @return array|LsDefSubject[]|ArrayCollection
     */
    public function getList()
    {
        $qb = $this->createQueryBuilder('s', 's.title')
            ->orderBy('s.title');

        return $qb->getQuery()->getResult();
    }
}
