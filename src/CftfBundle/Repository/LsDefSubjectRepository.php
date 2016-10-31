<?php

namespace CftfBundle\Repository;

use CftfBundle\Entity\LsDefSubject;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LsDefSubjectRepository
 */
class LsDefSubjectRepository extends AbstractLsDefinitionRepository
{
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
