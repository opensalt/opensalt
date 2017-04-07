<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * LsDefAssociationGrouping
 *
 * @ORM\Table(name="ls_def_association_grouping")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefAssociationGroupingRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LsDefAssociationGrouping extends AbstractLsDefinition implements CaseApiInterface
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
