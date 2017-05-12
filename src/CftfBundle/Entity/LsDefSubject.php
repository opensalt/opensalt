<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * LsDefSubject
 *
 * @ORM\Table(name="ls_def_subject")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\LsDefSubjectRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LsDefSubject extends AbstractLsDefinition implements CaseApiInterface
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
     * @return string
     */
    public function getHierarchyCode()
    {
        return $this->hierarchyCode;
    }

    /**
     * @param string $hierarchyCode
     *
     * @return LsDefSubject
     */
    public function setHierarchyCode($hierarchyCode): LsDefSubject
    {
        $this->hierarchyCode = $hierarchyCode;

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
