<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFAssociationGrouping
{
    public UuidInterface $identifier;
    public string $uri;
    public string $title;
    public ?string $description = null;
    public \DateTimeInterface $lastChangeDateTime;
}
