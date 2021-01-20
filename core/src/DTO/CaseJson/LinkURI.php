<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class LinkURI
{
    /** @var string */
    public $title;

    /** @var UuidInterface */
    public $identifier;

    /** @var string */
    public $uri;
}
