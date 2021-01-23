<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFConcept
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $title;

    /** @var string */
    public $keywords;

    /** @var string */
    public $hierarchyCode;

    /** @var string */
    public $description;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
