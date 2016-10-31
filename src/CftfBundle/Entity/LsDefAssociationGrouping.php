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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;


    /**
     * Set name
     *
     * @param string $name
     *
     * @return LsDefAssociationGrouping
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

