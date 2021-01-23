<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CFRubricCriterion
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $category;

    /** @var string */
    public $description;

    /**
     * @var LinkURI
     *
     * @SerializedName("CFItemURI")
     */
    public $cfItemURI;

    /** @var float */
    public $weight;

    /** @var int */
    public $position;

    /** @var string */
    public $rubricId;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;

    /**
     * @var CFRubricCriterionLevel[]|array
     *
     * @SerializedName("CFRubricCriterionLevels")
     */
    public $cfRubricCriterionLevels;
}
