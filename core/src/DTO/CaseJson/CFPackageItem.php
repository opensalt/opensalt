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
        'uri',
        'fullStatement',
        'lastChangeDateTime',
    ]
)]
class CFPackageItem
{
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'The full statement of the item',
        type: 'string',
    )]
    public string $fullStatement;

    #[OA\Property(
        description: 'An alternative label for the item',
        type: 'string',
        nullable: true,
    )]
    public ?string $alternativeLabel = null;

    #[OA\Property(
        description: 'The item type',
        type: 'string',
        nullable: true,
    )]
    #[SerializedName('CFItemType')]
    public ?string $cfItemType = null;

    #[OA\Property(
        description: 'The human coding scheme for the item',
        type: 'string',
        nullable: true,
    )]
    public ?string $humanCodingScheme = null;

    #[OA\Property(
        description: 'The list enumeration for the item',
        type: 'string',
        nullable: true,
    )]
    public ?string $listEnumeration = null;

    #[OA\Property(
        description: 'An abbreviated statement for the item',
        type: 'string',
        nullable: true,
    )]
    public ?string $abbreviatedStatement = null;

    /** @var string[]|array|null */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
        nullable: true,
    )]
    public ?array $conceptKeywords = null;

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    public ?LinkURI $conceptKeywordsURI = null;

    #[OA\Property(
        description: 'Additional notes about the item',
        type: 'string',
        nullable: true,
    )]
    public ?string $notes = null;

    /** @var string[]|string|null */
    #[OA\Property(
        title: 'Subject',
        description: 'The subject(s) of the item',
        nullable: true,
        oneOf: [
            new OA\Schema(
                type: 'array',
                items: new OA\Items(
                    type: 'string',
                ),
                minItems: 0,
            ),
            new OA\Schema(
                type: 'string',
            ),
        ],
    )]
    public string|array|null $subject = null;

    /** @var LinkURI[] */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: LinkURI::class)),
    )]
    public ?array $subjectURI = [];

    #[OA\Property(
        title: 'Language',
        description: 'Language in browser locale supported format (RFC 5646).',
        example: 'en',
        type: 'string',
        nullable: true,
    )]
    public ?string $language = null;

    /** @var string[]|string|null */
    #[OA\Property(
        title: 'Education level',
        description: 'The education level(s) that the item is intended',
        nullable: true,
        oneOf: [
            new OA\Schema(
                type: 'array',
                items: new OA\Items(
                    type: 'string',
                ),
            ),
            new OA\Schema(
                type: 'string',
            ),
        ],
    )]
    public string|array|null $educationLevel = null;

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    #[SerializedName('CFItemTypeURI')]
    public ?LinkURI $cfItemTypeURI = null;

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    public ?LinkURI $licenseURI = null;

    #[OA\Property(
        title: 'Status start date',
        description: 'The date the status started.',
        type: 'string',
        nullable: true,
        oneOf: [
            new OA\Schema(
                format: 'date',
            ),
            new OA\Schema(
                format: 'date-time',
            ),
        ],
    )]
    public ?\DateTimeInterface $statusStartDate = null;

    #[OA\Property(
        title: 'Status end date',
        description: 'The date the status ended.',
        type: 'string',
        nullable: true,
        oneOf: [
            new OA\Schema(
                format: 'date',
            ),
            new OA\Schema(
                format: 'date-time',
            ),
        ],
    )]
    public ?\DateTimeInterface $statusEndDate = null;

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
    public ?array $extensions = null;
}
