<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;

#[OA\Schema(
    required: [
        'identifier',
        'uri',
        'lastChangeDateTime',
    ]
)]
class CFRubricCriterionLevel
{
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $quality = null;

    #[OA\Property(
        type: 'number',
        format: 'float',
        nullable: true,
    )]
    public ?float $score = null;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $feedback = null;

    #[OA\Property(
        type: 'integer',
        nullable: true,
    )]
    public ?int $position = null;

    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
        nullable: true,
    )]
    public ?string $rubricCriterionId = null;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        description: 'Additional information about the rubric criterion level as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
