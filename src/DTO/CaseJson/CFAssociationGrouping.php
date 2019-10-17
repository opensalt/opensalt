<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFAssociationGrouping
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
}
