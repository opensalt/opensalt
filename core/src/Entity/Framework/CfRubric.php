<?php

namespace App\Entity\Framework;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\CfRubricRepository")
 */
class CfRubric extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @ORM\Column(name="title", type="text", length=65535, nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private ?string $description = null;

    /**
     * @var Collection<CfRubricCriterion>
     *
     * @ORM\OneToMany(targetEntity="CfRubricCriterion", mappedBy="rubric", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private Collection $criteria;

    public function __construct(UuidInterface|string|null $identifier = null)
    {
        parent::__construct($identifier);
        $this->criteria = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): CfRubric
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CfRubric
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<CfRubricCriterion>
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function addCriterion(CfRubricCriterion $criterion): CfRubric
    {
        $this->criteria[] = $criterion;

        return $this;
    }

    public function removeCriterion(CfRubricCriterion $criterion): CfRubric
    {
        $this->criteria->removeElement($criterion);

        return $this;
    }
}
