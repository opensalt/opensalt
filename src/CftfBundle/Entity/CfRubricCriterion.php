<?php

namespace CftfBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class CfRubricCriterion
 *
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric_criterion")
 * @ORM\Entity(repositoryClass="CftfBundle\Repository\CfRubricCriterionRepository")
 *
 * @Serializer\VirtualProperty(
 *     "itemUri",
 *     exp="service('salt.api.v1p1.utils').getApiUrl(object.getItem())",
 *     options={
 *         @Serializer\SerializedName("CFItemURI"),
 *         @Serializer\Expose()
 *     }
 * )
 *
 * @Serializer\VirtualProperty(
 *     "rubricId",
 *     exp="object.getRubric()?object.getRubric().getIdentifier():null",
 *     options={
 *         @Serializer\SerializedName("rubricId"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class CfRubricCriterion extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", nullable=true)
     *
     * @Serializer\Expose()
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     *
     * @Serializer\Expose()
     */
    private $description;

    /**
     * @var LsItem
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsItem", inversedBy="criteria")
     * @ORM\JoinColumn(name="ls_item_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $item;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", nullable=true)
     *
     * @Serializer\Expose()
     */
    private $weight;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     *
     * @Serializer\Expose()
     */
    private $position;

    /**
     * @var CfRubric
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\CfRubric", inversedBy="criteria")
     * @ORM\JoinColumn(name="rubric_id", referencedColumnName="id")
     *
     * @Serializer\Exclude()
     */
    private $rubric;

    /**
     * @var Collection|CfRubricCriterionLevel[]
     *
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\CfRubricCriterionLevel", mappedBy="criterion")
     *
     * @Serializer\Exclude()
     */
    private $levels;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->levels = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     *
     * @return CfRubricCriterion
     */
    public function setCategory($category): CfRubricCriterion
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CfRubricCriterion
     */
    public function setDescription($description): CfRubricCriterion
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return LsItem
     */
    public function getItem(): LsItem
    {
        return $this->item;
    }

    /**
     * @param LsItem $item
     *
     * @return CfRubricCriterion
     */
    public function setItem($item): CfRubricCriterion
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     *
     * @return CfRubricCriterion
     */
    public function setWeight($weight): CfRubricCriterion
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return CfRubricCriterion
     */
    public function setPosition($position): CfRubricCriterion
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return CfRubric
     */
    public function getRubric(): CfRubric
    {
        return $this->rubric;
    }

    /**
     * @param CfRubric $rubric
     *
     * @return CfRubricCriterion
     */
    public function setRubric($rubric): CfRubricCriterion
    {
        $this->rubric = $rubric;

        return $this;
    }

    /**
     * @return CfRubricCriterionLevel[]|Collection
     */
    public function getLevels()
    {
        return $this->levels;
    }

    /**
     * @param CfRubricCriterionLevel[]|Collection $levels
     *
     * @return CfRubricCriterion
     */
    public function setLevels($levels): CfRubricCriterion
    {
        $this->levels = $levels;

        return $this;
    }

    /**
     * @param CfRubricCriterionLevel $level
     *
     * @return CfRubricCriterion
     */
    public function addLevel(CfRubricCriterionLevel $level): CfRubricCriterion
    {
        $this->levels[] = $level;

        return $this;
    }
}
