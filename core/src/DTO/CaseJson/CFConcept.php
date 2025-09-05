<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;

#[OA\Schema(
    required: [
        'identifier',
        'uri',
        'title',
        'hierarchyCode',
        'lastChangeDateTime',
    ]
)]
class CFConcept
{
    #[OA\Property(
        description: 'A unique identifier for the concept',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'A resolvable URL for the concept',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'The title of the concept',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'Keywords associated with the concept',
        type: 'string',
        nullable: true,
    )]
    public ?string $keywords = null;

    #[OA\Property(
        description: 'The hierarchy code for the concept',
        type: 'string',
    )]
    public string $hierarchyCode;

    #[OA\Property(
        description: 'A description of the concept',
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[OA\Property(
        description: 'The date and time the concept was last changed',
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        description: 'Additional information about the concept as key-value pairs',
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
