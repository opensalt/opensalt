<?php

namespace App\DTO\CaseJson;

use Ramsey\Uuid\UuidInterface;

class CFRubricCriterionLevel
{
    public UuidInterface $identifier;
    public string $uri;
    public ?string $description = null;
    public ?string $quality = null;
    public ?float $score = null;
    public ?string $feedback = null;
    public ?int $position = null;
    public ?string $rubricCriterionId = null;
    public \DateTimeInterface $lastChangeDateTime;
}
