<?php

declare(strict_types=1);

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
    public \DateTimeInterface $lastChangeDateTime;
    public ?array $extensions = null;
}
