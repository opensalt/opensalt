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
        'lastChangeDateTime',
    ]
)]
class CFAssociationGrouping
{
    #[OA\Property(
        description: 'A unique identifier for the association grouping',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'A resolvable URL for the association grouping',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'The title of the association grouping',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'A description of the association grouping',
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[OA\Property(
        description: 'The date and time the association grouping was last changed',
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        description: 'Additional information about the association grouping as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
