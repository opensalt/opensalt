<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;

#[OA\Schema(
    required: [
        'identifier',
        'uri',
        'creator',
        'title',
        'lastChangeDateTime',
    ]
)]
class CFPackageDocument
{
    #[OA\Property(
        description: 'A unique identifier for the document',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        description: 'A resolvable URL for the document',
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        description: 'The CASE version of the document',
        type: 'string',
        enum: ['1.1'],
        nullable: true,
    )]
    public ?string $caseVersion = null;

    #[OA\Property(
        description: 'The type of framework',
        type: 'string',
        nullable: true,
    )]
    public ?string $frameworkType = null;

    #[OA\Property(
        description: 'The creator of the document',
        type: 'string',
    )]
    public string $creator;

    #[OA\Property(
        description: 'The title of the document',
        type: 'string',
    )]
    public string $title;

    #[OA\Property(
        description: 'The date and time the document was last changed',
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    #[OA\Property(
        description: 'The official source URL of the document',
        type: 'string',
        format: 'uri',
        nullable: true,
    )]
    public ?string $officialSourceURL = null;

    #[OA\Property(
        description: 'The publisher of the document',
        type: 'string',
        nullable: true,
    )]
    public ?string $publisher = null;

    #[OA\Property(
        description: 'A description of the document',
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    /** @var string[]|string|null */
    #[OA\Property(
        title: 'Subject',
        description: 'The subject(s) of the document',
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
        description: 'Array of subject URIs',
        type: 'array',
        items: new OA\Items(ref: new Model(type: LinkURI::class)),
        nullable: true,
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

    #[OA\Property(
        description: 'The version of the document',
        type: 'string',
        nullable: true,
    )]
    public ?string $version = null;

    #[OA\Property(
        description: 'The adoption status of the document',
        type: 'string',
        nullable: true,
    )]
    public ?string $adoptionStatus = null;

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

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    public ?LinkURI $licenseURI = null;

    #[OA\Property(
        description: 'Additional notes about the document',
        type: 'string',
        nullable: true,
    )]
    public ?string $notes = null;

    #[OA\Property(
        description: 'Additional information about the document as key-value pairs',
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
