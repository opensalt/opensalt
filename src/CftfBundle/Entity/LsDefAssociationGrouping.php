<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefAssociationGrouping
 *
 * @ORM\Table(name="ls_def_association_grouping")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefAssociationGroupingRepository")
 */
class LsDefAssociationGrouping extends AbstractLsDefinition
{
    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="associationGroupings")
     */
    private $lsDoc;

    /**
     * @return LsDoc
     */
    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * @param LsDoc $lsDoc
     *
     * @return LsDefAssociationGrouping
     */
    public function setLsDoc(?LsDoc $lsDoc)
    {
        $this->lsDoc = $lsDoc;

        return $this;
    }
}
