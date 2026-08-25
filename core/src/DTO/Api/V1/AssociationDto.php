<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use App\Entity\Framework\LsAssociation;
use App\ObjectMapper\AssociationFromDto;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: LsAssociation::class, transform: [AssociationFromDto::class, 'mapApiV1Association'])]
class AssociationDto
{
    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The identifier is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The identifier is required', groups: ['view'])]
    #[OA\Property(
        description: 'A unique identifier for the association',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
        nullable: true,
    )]
    public ?UuidInterface $identifier = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The uri is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The uri is required', groups: ['view'])]
    #[OA\Property(
        description: 'A resolvable URL for the association',
        type: 'string',
        format: 'uri',
        nullable: true,
    )]
    public ?string $uri = null;

    #[Groups(['view'])]
    #[Assert\NotNull(message: 'The document reference is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The document reference is required', groups: ['view'])]
    #[Map(if: false)]
    public ?LinkURI $CFDocumentURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The associationType is required', groups: ['create', 'update'])]
    #[Assert\NotBlank(message: 'The associationType is required', groups: ['create', 'update'])]
    #[Assert\AtLeastOneOf([
        new Assert\Choice(choices: LsAssociation::BASE_TYPES),
        new Assert\Regex(pattern: '/^ext:[a-zA-Z0-9._-]+$/'),
    ], message: 'The associationType must be a defined type or an extended type of the regex form /^ext:[a-zA-Z0-9._-]+$/', groups: ['create', 'update'])]
    #[OA\Property(
        description: 'The way the origin is associated with the destination.',
        type: 'string',
        anyOf: [
            new OA\Schema(
                title: 'Defined CASE type',
                description: 'Defined enumeration of associationTypes',
                enum: LsAssociation::BASE_TYPES,
            ),
            new OA\Schema(
                title: 'Extended type',
                description: 'Extended type of association',
                pattern: '^ext:[a-zA-Z0-9._-]+$',
            ),
        ]
    )]
    #[Map(target: 'type')]
    public string $associationType;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        title: 'Origin node',
        description: 'The origin node for the association',
    )]
    #[Map(if: false)]
    public ?LinkGenURI $originNodeURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        title: 'Destination node',
        description: 'The destination node for the association',
    )]
    #[Map(if: false)]
    public ?LinkGenURI $destinationNodeURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        description: 'Sequence for the association, especially useful for isChildOf to determine the order of the children',
        type: 'integer',
        nullable: true,
    )]
    public ?int $sequenceNumber = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $notes = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date-time',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The date is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The date is required', groups: ['view'])]
    #[Map(if: false)]
    public ?\DateTimeInterface $lastChangeDateTime = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        description: 'Additional information about the association as key-value pairs',
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
