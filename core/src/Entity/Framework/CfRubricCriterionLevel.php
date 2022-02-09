<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric_criterion_level")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\CfRubricCriterionLevelRepository")
 */
class CfRubricCriterionLevel extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(name="quality", type="text", length=65535, nullable=true)
     */
    private ?string $quality = null;

    /**
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    private ?float $score = null;

    /**
     * @ORM\Column(name="feedback", type="text", length=65535, nullable=true)
     */
    private ?string $feedback = null;

    /**
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private ?int $position = null;

    /**
     * @ORM\ManyToOne(targetEntity="CfRubricCriterion", inversedBy="levels")
     * @ORM\JoinColumn(name="criterion_id", referencedColumnName="id", nullable=false)
     */
    private CfRubricCriterion $criterion;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CfRubricCriterionLevel
    {
        $this->description = $description;

        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(?string $quality): CfRubricCriterionLevel
    {
        $this->quality = $quality;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): CfRubricCriterionLevel
    {
        $this->score = $score;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): CfRubricCriterionLevel
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): CfRubricCriterionLevel
    {
        $this->position = $position;

        return $this;
    }

    public function getCriterion(): CfRubricCriterion
    {
        return $this->criterion;
    }

    public function setCriterion(CfRubricCriterion $criterion): CfRubricCriterionLevel
    {
        $this->criterion = $criterion;

        return $this;
    }
}
