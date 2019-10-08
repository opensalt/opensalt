<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFRubricCriterionLevel
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $description;

    /** @var string */
    public $quality;

    /** @var float */
    public $score;

    /** @var string */
    public $feedback;

    /** @var int */
    public $position;

    /** @var string */
    public $rubricCriterionId;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
