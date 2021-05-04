<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * LsDefConcept.
 *
 * @ORM\Table(name="ls_def_concept")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefConceptRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LsDefConcept extends AbstractLsDefinition implements CaseApiInterface
{
    /**
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("hierarchyCode")
     */
    private string $hierarchyCode;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="text", nullable=true)
     *
     * @Serializer\Expose()
     */
    private ?string $keywords = null;

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): LsDefConcept
    {
        $this->hierarchyCode = $hierarchyCode;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): LsDefConcept
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * String to represent this subject.
     */
    public function __toString(): string
    {
        return $this->getTitle() ?? ('Concept: '.$this->getIdentifier());
    }
}
