<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class RubricDto
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
    #[Assert\NotBlank(message: 'Cannot be an empty string', groups: ['create', 'update', 'view'])]
    public string $title;

    #[Groups(['view'])]
    #[Assert\NotNull(message: 'The date is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The date is required', groups: ['view'])]
    public \DateTimeInterface $lastChangeDateTime;

    #[Groups(['create', 'update', 'view'])]
    public ?string $description = null;

    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            description: 'Criteria for the rubric',
        ),
        nullable: true,
    )]
    #[Map('criteria')]
    public ?array $CFRubricCriteria = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;

    public function __construct()
    {
        $this->lastChangeDateTime = new \DateTimeImmutable();
        $this->CFRubricCriteria = [];
    }
}
