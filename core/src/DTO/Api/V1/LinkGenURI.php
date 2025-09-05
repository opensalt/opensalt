<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Groups(['create', 'update', 'view'])]
class LinkGenURI
{
    #[OA\Property(
        description: 'A title for the linked object.',
        type: 'string',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The title must not be empty')]
    #[Assert\NotBlank(message: 'The title must not be empty')]
    public ?string $title = null;

    #[OA\Property(
        description: 'The identifier of the linked object.',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
    )]
    #[Assert\NotNull(message: 'The identifier must not be empty')]
    #[Assert\NotBlank(message: 'The identifier must not be empty')]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'The URI of the linked object.',
        type: 'string',
        format: 'uri',
    )]
    #[Assert\NotNull(message: 'The uri must not be empty')]
    #[Assert\NotBlank(message: 'The uri must not be empty')]
    public ?string $uri = null;

    #[OA\Property(
        description: 'The type of the linked object.',
        type: 'string',
        nullable: true,
    )]
    public ?string $targetType = null;
}
