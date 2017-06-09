<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * LsDefConcept
 *
 * @ORM\Table(name="ls_def_concept")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefConceptRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LsDefConcept extends AbstractLsDefinition implements CaseApiInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("hierarchyCode")
     */
    private $hierarchyCode;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="text")
     *
     * @Serializer\Expose()
     */
    private $keywords;


    /**
     * @return string
     */
    public function getHierarchyCode()
    {
        return $this->hierarchyCode;
    }

    /**
     * @param string $hierarchyCode
     *
     * @return LsDefConcept
     */
    public function setHierarchyCode($hierarchyCode): LsDefConcept
    {
        $this->hierarchyCode = $hierarchyCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     *
     * @return LsDefConcept
     */
    public function setKeywords($keywords): LsDefConcept
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * String to represent this subject
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }
}
