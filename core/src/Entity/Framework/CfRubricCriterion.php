<?php

namespace App\Entity\Framework;

use App\Repository\Framework\CfRubricCriterionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'rubric_criterion')]
#[ORM\Entity(repositoryClass: CfRubricCriterionRepository::class)]
class CfRubricCriterion extends AbstractLsBase implements CaseApiInterface
{
    #[ORM\Column(name: 'category', type: 'string', nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'description', type: 'text', length: 65535, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: LsItem::class, inversedBy: 'criteria')]
    #[ORM\JoinColumn(name: 'ls_item_id', referencedColumnName: 'id')]
    private ?LsItem $item = null;

    #[ORM\Column(name: 'weight', type: 'float', nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: CfRubric::class, inversedBy: 'criteria')]
    #[ORM\JoinColumn(name: 'rubric_id', referencedColumnName: 'id', nullable: false)]
    private CfRubric $rubric;

    /**
     * @var Collection<array-key, CfRubricCriterionLevel>
     */
    #[ORM\OneToMany(mappedBy: 'criterion', targetEntity: CfRubricCriterionLevel::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $levels;

    public function __construct(UuidInterface|string|null $identifier = null)
    {
        parent::__construct($identifier);
        $this->levels = new ArrayCollection();
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getItem(): ?LsItem
    {
        return $this->item;
    }

    public function setItem(?LsItem $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getRubric(): ?CfRubric
    {
        return $this->rubric;
    }

    public function setRubric(CfRubric $rubric): static
    {
        $this->rubric = $rubric;

        return $this;
    }

    /**
     * @return Collection<array-key, CfRubricCriterionLevel>
     */
    public function getLevels(): Collection
    {
        return $this->levels;
    }

    public function addLevel(CfRubricCriterionLevel $level): static
    {
        $this->levels[] = $level;

        return $this;
    }

    public function removeLevel(CfRubricCriterionLevel $level): static
    {
        $this->levels->removeElement($level);

        return $this;
    }
}
