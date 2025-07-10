<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefLicenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_licence')]
#[ORM\Entity(repositoryClass: LsDefLicenceRepository::class)]
class LsDefLicence implements CaseApiInterface
{
    use IdentifiableTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;

    #[ORM\Column(name: 'title', type: 'string', length: 1024, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'licence_text', type: 'text')]
    private string $licenceText;

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

    public function getLicenceText(): string
    {
        return $this->licenceText;
    }

    public function setLicenceText(string $licenceText): void
    {
        $this->licenceText = $licenceText;
    }
}
