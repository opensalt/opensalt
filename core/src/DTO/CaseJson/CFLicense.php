<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFLicense
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
    public $licenseText;

    /** @var \DateTimeInterface */
    public $lastChangeDateTime;
}
