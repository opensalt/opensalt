<?php

namespace CftfBundle\Repository;

use CftfBundle\Entity\LsDefItemType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LsDefItemTypeRepository
 */
class LsDefItemTypeRepository extends AbstractLsDefinitionRepository
{
    /**
     * @return array|LsDefItemType[]|ArrayCollection
     */
    public function getList()
    {
        $qb = $this->createQueryBuilder('t', 't.code')
            ->orderBy('t.code');

        return $qb->getQuery()->getResult();
    }
}
