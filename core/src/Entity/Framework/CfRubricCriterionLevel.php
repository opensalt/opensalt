<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CfRubricCriterionLevel
 *
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric_criterion_level")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\CfRubricCriterionLevelRepository")
 */
class CfRubricCriterionLevel extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="quality", type="text", length=65535, nullable=true)
     */
    private $quality;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    private $score;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="text", length=65535, nullable=true)
     */
    private $feedback;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @var CfRubricCriterion
     *
     * @ORM\ManyToOne(targetEntity="CfRubricCriterion", inversedBy="levels")
     * @ORM\JoinColumn(name="criterion_id", referencedColumnName="id", nullable=false)
     */
    private $criterion;


    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): CfRubricCriterionLevel
    {
        $this->description = $description;

        return $this;
    }


    public function getQuality(): string
    {
        return $this->quality;
    }

    /**
     * @param string $quality
     */
    public function setQuality($quality): CfRubricCriterionLevel
    {
        $this->quality = $quality;

        return $this;
    }


    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @param float $score
     */
    public function setScore($score): CfRubricCriterionLevel
    {
        $this->score = $score;

        return $this;
    }


    public function getFeedback(): string
    {
        return $this->feedback;
    }

    /**
     * @param string $feedback
     */
    public function setFeedback($feedback): CfRubricCriterionLevel
    {
        $this->feedback = $feedback;

        return $this;
    }


    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position): CfRubricCriterionLevel
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
