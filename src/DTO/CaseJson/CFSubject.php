<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFSubject
{
    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;

    /** @var string */
    public $title;

    /** @var string */
    public $hierarchyCode;

    /** @var string */
    public $description;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
