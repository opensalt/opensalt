<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['create', 'update', 'view'])]
class LinkURI
{
    #[OA\Property(
        description: 'A title for the linked object.',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'The identifier of the linked object.',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'The URI of the linked object.',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;
}
