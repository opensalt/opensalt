<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefConcept
 *
 * @ORM\Table(name="ls_def_concept")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefConceptRepository")
 */
class LsDefConcept extends AbstractLsDefinition
{
    /**
     * @var string
     *
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     */
    private $hierarchyCode;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="text")
     */
    private $keywords;


    /**
     * @return string
     */
    public function getHierarchyCode() {
        return $this->hierarchyCode;
    }

    /**
     * @param string $hierarchyCode
     *
     * @return LsDefConcept
     */
    public function setHierarchyCode($hierarchyCode) {
        $this->hierarchyCode = $hierarchyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords() {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     *
     * @return LsDefConcept
     */
    public function setKeywords($keywords) {
        $this->keywords = $keywords;
        return $this;
    }
}
