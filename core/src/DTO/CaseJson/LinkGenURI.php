<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use OpenApi\Attributes as OA;

#[OA\Schema(
    required: [
        'title',
        'identifier',
        'uri',
    ]
)]
class LinkGenURI
{
    #[OA\Property(
        description: 'A title for the linked object.',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'The identifier of the linked object.',
        type: 'string',
    )]
    public string $identifier;

    #[OA\Property(
        description: 'The URI of the linked object.',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'Any of: enum "CASE" or pattern (ext:)[a-zA-Z0-9\\.\\-_]+',
        type: 'string',
        nullable: true,
        anyOf: [
            new OA\Schema(
                enum: ['CASE'],
            ),
            new OA\Schema(
                pattern: '^(ext:)[a-zA-Z0-9._-]+$',
            ),
        ]
    )]
    public ?string $targetType = null;
}
