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
class CFItemType
{
    #[OA\Property(
        description: 'A unique identifier for the item type',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'A resolvable URL for the item type',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'The title of the item type',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'A description of the item type',
        type: 'string',
    )]
    public string $description;

    #[OA\Property(
        description: 'The hierarchy code for the item type',
        type: 'string',
    )]
    public string $hierarchyCode;

    #[OA\Property(
        description: 'The type code for the item type',
        type: 'string',
        nullable: true,
    )]
    public ?string $typeCode = null;

    #[OA\Property(
        description: 'The date and time the item type was last changed',
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        description: 'Additional information about the item type as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
