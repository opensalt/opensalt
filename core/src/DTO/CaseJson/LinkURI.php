<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class LinkURI
    public string $title;
    public UuidInterface $identifier;
    public string $uri;
}
