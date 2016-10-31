<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefItemType
 *
 * @ORM\Table(name="ls_def_item_type")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefItemTypeRepository")
 */
class LsDefItemType extends AbstractLsDefinition
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     */
    private $hierarchyCode;


    /**
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @param string $code
     * @return LsDefItemType
     */
    public function setCode($code) {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getHierarchyCode() {
        return $this->hierarchyCode;
    }

    /**
     * @param string $hierarchyCode
     * @return LsDefItemType
     */
    public function setHierarchyCode($hierarchyCode) {
        $this->hierarchyCode = $hierarchyCode;
        return $this;
    }
}

