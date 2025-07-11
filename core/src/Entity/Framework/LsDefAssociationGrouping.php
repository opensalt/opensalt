<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefAssociationGroupingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'ls_def_association_grouping')]
#[ORM\Entity(repositoryClass: LsDefAssociationGroupingRepository::class)]
class LsDefAssociationGrouping implements CaseApiInterface
{
    use IdentifiableTrait;
    use ChangedAtTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;

    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'associationGroupings')]
    #[Assert\NotNull]
    private ?LsDoc $lsDoc = null;

    #[ORM\Column(name: 'title', type: 'string', length: 1024, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    public function __construct(?string $identifier = null)
    {
        $this->setIdentifierOrNew($identifier);

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }

    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    public function setLsDoc(?LsDoc $lsDoc): void
    {
        $this->lsDoc = $lsDoc;
    }

    /**
     * Create a duplicate of the lsDefAssociationGrouping into a new document.
     */
    public function duplicateToLsDoc(LsDoc $newLsDoc): static
    {
        $newAssociationGrouping = clone $this;
        $newAssociationGrouping->setLsDoc($newLsDoc);

        return $newAssociationGrouping;
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
}
