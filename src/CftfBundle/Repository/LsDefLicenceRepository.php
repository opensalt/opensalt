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
        $qBuilder = $this->createQueryBuilder('s', 's.title')
            ->orderBy('s.title');

        return $qBuilder->getQuery()->getResult();
    }
}
