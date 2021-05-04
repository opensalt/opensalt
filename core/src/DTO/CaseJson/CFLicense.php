<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFLicense
{
    public UuidInterface $identifier;
    public string $uri;
    public string $title;
    public ?string $description = null;
    public string $licenseText;
    public \DateTimeInterface $lastChangeDateTime;
}
