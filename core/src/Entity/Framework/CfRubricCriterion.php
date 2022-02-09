<?php

namespace App\Entity\Framework;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric_criterion")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\CfRubricCriterionRepository")
 */
class CfRubricCriterion extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @ORM\Column(name="category", type="string", nullable=true)
     */
    private ?string $category = null;

    /**
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="LsItem", inversedBy="criteria")
     * @ORM\JoinColumn(name="ls_item_id", referencedColumnName="id")
     */
    private ?LsItem $item = null;

    /**
     * @ORM\Column(name="weight", type="float", nullable=true)
     */
    private ?float $weight = null;

    /**
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private ?int $position = null;

    /**
     * @ORM\ManyToOne(targetEntity="CfRubric", inversedBy="criteria")
     * @ORM\JoinColumn(name="rubric_id", referencedColumnName="id", nullable=false)
     */
    private CfRubric $rubric;

    /**
     * @var Collection<CfRubricCriterionLevel>
     *
     * @ORM\OneToMany(targetEntity="CfRubricCriterionLevel", mappedBy="criterion", orphanRemoval=true, cascade={"persist", "remove"})
     */
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

    public function setCategory(?string $category): CfRubricCriterion
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CfRubricCriterion
    {
        $this->description = $description;

        return $this;
    }

    public function getItem(): ?LsItem
    {
        return $this->item;
    }

    public function setItem(?LsItem $item): CfRubricCriterion
    {
        $this->item = $item;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): CfRubricCriterion
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): CfRubricCriterion
    {
        $this->position = $position;

        return $this;
    }

    public function getRubric(): ?CfRubric
    {
        return $this->rubric;
    }

    public function setRubric(CfRubric $rubric): CfRubricCriterion
    {
        $this->rubric = $rubric;

        return $this;
    }

    /**
     * @return Collection<CfRubricCriterionLevel>
     */
    public function getLevels(): Collection
    {
        return $this->levels;
    }

    public function addLevel(CfRubricCriterionLevel $level): CfRubricCriterion
    {
        $this->levels[] = $level;

        return $this;
    }

    public function removeLevel(CfRubricCriterionLevel $level): CfRubricCriterion
    {
        $this->levels->removeElement($level);

        return $this;
    }
}
