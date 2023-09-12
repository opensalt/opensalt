<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefConceptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_concept')]
#[ORM\Entity(repositoryClass: LsDefConceptRepository::class)]
class LsDefConcept extends AbstractLsDefinition implements CaseApiInterface
{
    #[ORM\Column(name: 'hierarchy_code', type: 'string', length: 255)]
    private string $hierarchyCode;

    #[ORM\Column(name: 'keywords', type: 'text', nullable: true)]
    private ?string $keywords = null;

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): void
    {
        $this->hierarchyCode = $hierarchyCode;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * String to represent this subject.
     */
    public function __toString(): string
    {
        return $this->getTitle() ?? ('Concept: '.$this->getIdentifier());
    }
}
