<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;

#[OA\Schema(
    required: [
        'title',
        'identifier',
        'uri',
    ]
)]
class LinkURI
{
    #[OA\Property(
        description: 'A title for what is linked.',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'The identifier of what is linked.',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'The URI of what is linked.',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;
}
