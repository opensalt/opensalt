<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFItemType
{
    public UuidInterface $identifier;
    public string $uri;
    public string $title;
    public string $description;
    public string $hierarchyCode;
    public ?string $typeCode = null;
    public string $lastChangeDateTime;
}
