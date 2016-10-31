<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefSubject
 *
 * @ORM\Table(name="ls_def_subject")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefSubjectRepository")
 */
class LsDefSubject extends AbstractLsDefinition
{
    /**
     * @var string
     *
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     */
    private $hierarchyCode;


    /**
     * @return string
     */
    public function getHierarchyCode() {
        return $this->hierarchyCode;
    }

    /**
     * @param string $hierarchyCode
     * @return LsDefSubject
     */
    public function setHierarchyCode($hierarchyCode) {
        $this->hierarchyCode = $hierarchyCode;
        return $this;
    }
}

