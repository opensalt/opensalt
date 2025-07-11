<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefItemTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_item_type')]
#[ORM\Entity(repositoryClass: LsDefItemTypeRepository::class)]
class LsDefItemType implements CaseApiInterface
{
    use IdentifiableTrait;
    use ChangedAtTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;

    #[ORM\Column(name: 'title', type: 'string', length: 1024, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'code', type: 'string', length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(name: 'hierarchy_code', type: 'string', length: 255)]
    private string $hierarchyCode;

    public function __construct(?string $identifier = null)
    {
        $this->setIdentifierOrNew($identifier);

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
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
        return $this->getTitle() ?? $this->getIdentifier();
    }
}
