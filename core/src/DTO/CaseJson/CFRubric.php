<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CFRubric
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;

    /**
     * @var CFRubricCriterion[]|array
     *
     * @SerializedName("CFRubricCriteria")
     */
    public $cfRubricCriteria;
}
