<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="ls_def_subject")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefSubjectRepository")
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

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): LsDefSubject
    {
        $this->hierarchyCode = $hierarchyCode;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? 'Subject: '.$this->identifier;
    }
}
