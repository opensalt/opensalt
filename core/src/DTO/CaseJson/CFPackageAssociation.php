<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[OA\Schema(
    required: [
        'identifier',
        'associationType',
        'uri',
        'originNodeURI',
        'destinationNodeURI',
        'lastChangeDateTime',
    ]
)]
class CFPackageAssociation
{
    #[OA\Property(
        description: 'A unique identifier for the association',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        title: 'Association type',
        description: 'The type of the association',
        type: 'string',
        anyOf: [
            new OA\Schema(
                title: 'Defined CASE type',
                description: 'Defined enumeration of associationTypes',
                enum: [
                    'isChildOf',
                    'isPeerOf',
                    'isPartOf',
                    'exactMatchOf',
                    'precedes',
                    'isRelatedTo',
                    'replacedBy',
                    'exemplar',
                    'hasSkillLevel',
                    'isTranslationOf',
                ],
            ),
            new OA\Schema(
                title: 'Extended type',
                description: 'Extended type of association',
                pattern: '^(ext:)[a-zA-Z0-9._-]+$',
            ),
        ],
    )]
    public string $associationType;

    #[OA\Property(
        description: 'Sequence for the association, especially useful for isChildOf to determine the order of the children',
        type: 'integer',
        format: 'int32',
        nullable: true,
    )]
    public ?int $sequenceNumber = null;

    #[OA\Property(
        description: 'A resolvable URL for the association',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        title: 'Origin node',
        description: 'The origin node for the association',
        ref: new Model(type: LinkGenURI::class)
    )]
    public LinkGenURI $originNodeURI;

    #[OA\Property(
        title: 'Destination node',
        description: 'The destination node for the association',
        ref: new Model(type: LinkGenURI::class)
    )]
    public LinkGenURI $destinationNodeURI;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $notes = null;

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    #[SerializedName('CFAssociationGroupingURI')]
    public ?LinkURI $cfAssociationGroupingURI = null;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = [];
}
