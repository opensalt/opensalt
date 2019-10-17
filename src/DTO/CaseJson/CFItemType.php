<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFItemType
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var string */
    public $hierarchyCode;

    /** @var string */
    public $typeCode;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
