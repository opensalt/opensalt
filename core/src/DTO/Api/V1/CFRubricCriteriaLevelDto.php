<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CFRubricCriteriaLevelDto
{
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The identifier is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The identifier is required', groups: ['view'])]
    public ?UuidInterface $identifier = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'uri',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The uri is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The uri is required', groups: ['view'])]
    public ?string $uri = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $quality = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'number',
        format: 'float',
        nullable: true,
    )]
    public ?float $score = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $feedback = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'integer',
        nullable: true,
    )]
    public ?int $position = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;

    #[Groups(['view'])]
    #[Assert\NotNull(message: 'The date is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The date is required', groups: ['view'])]
    public ?\DateTimeInterface $lastChangeDateTime = null;

    public function __construct()
    {
        $this->lastChangeDateTime = new \DateTimeImmutable();
    }
}
