<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefSubjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_subject')]
#[ORM\Entity(repositoryClass: LsDefSubjectRepository::class)]
class LsDefSubject extends AbstractLsBase implements CaseApiInterface
{
    #[ORM\Column(name: 'title', type: 'string', length: 1024, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'hierarchy_code', type: 'string', length: 255)]
    private string $hierarchyCode;

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? $this->title;
    }

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): void
    {
        $this->hierarchyCode = $hierarchyCode;
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? 'Subject: '.$this->identifier;
    }
}
