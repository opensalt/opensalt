<?php

namespace CftfBundle\Repository;

/**
 * LsDefLicenceRepository
 */
class LsDefLicenceRepository extends AbstractLsDefinitionRepository
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
