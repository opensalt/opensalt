<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class LinkURI
{
    public string $title;
    public UuidInterface $identifier;
    public string $uri;
}
