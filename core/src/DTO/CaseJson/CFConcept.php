<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFConcept
{
    public UuidInterface $identifier;
    public string $uri;
    public string $title;
    public ?string $keywords = null;
    public string $hierarchyCode;
    public ?string $description = null;
    public \DateTimeInterface $lastChangeDateTime;
}
