<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

class LinkGenURI
{
    public string $title;
    public string $identifier;
    public string $uri;
    public ?string $targetType = null;
}
