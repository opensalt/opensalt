<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefConceptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_concept')]
#[ORM\Entity(repositoryClass: LsDefConceptRepository::class)]
class LsDefConcept implements CaseApiInterface
{
    use IdentifiableTrait;
    use ChangedAtTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;

    #[ORM\Column(name: 'title', type: 'string', length: 1024)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'hierarchy_code', type: 'string', length: 255)]
    private string $hierarchyCode;

    #[ORM\Column(name: 'keywords', type: 'text', nullable: true)]
    private ?string $keywords = null;

    public function __construct(?string $identifier = null)
    {
        $this->setIdentifierOrNew($identifier);

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
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
        return $this->getTitle();
    }
}
