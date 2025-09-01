<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ItemDto
{
    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The fullStatement is required')]
    #[Assert\NotBlank(message: 'The fullStatement is required')]
    #[Map('fullStatement')]
    public string $fullStatement;

    #[Groups(['create'])]
    #[OA\Property(
        description: 'Identifier of the parent item of this item',
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
        nullable: true,
    )]
    #[Map('parent.identifier')]
    public ?string $parentIdentifier = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The identifier is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The identifier is required', groups: ['view'])]
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
        nullable: true,
    )]
    #[Map('identifier')]
    public ?UuidInterface $identifier = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotNull(message: 'The uri is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The uri is required', groups: ['view'])]
    #[OA\Property(
        type: 'string',
        format: 'uri',
        nullable: true,
    )]
    #[Map('uri')]
    public ?string $uri = null;

    #[Groups(['view'])]
    #[Assert\NotNull(message: 'The document reference is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The document reference is required', groups: ['view'])]
    #[Map('lsDoc.uri')]
    public ?LinkURI $CFDocumentURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('abbreviatedStatement')]
    public ?string $abbreviatedStatement = null;

    #[Groups(['create', 'update', 'view'])]
    #[SerializedName('CFItemType')]
    #[Map('itemType.title')]
    public ?string $cfItemType = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('humanCodingScheme')]
    public ?string $humanCodingScheme = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('alternativeLabel')]
    public ?string $alternativeLabel = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('listEnumInSource')]
    public ?string $listEnumeration = null;

    /** @var string[]|null */
    #[Groups(['create', 'update', 'view'])]
    #[Map('conceptKeywords')]
    public ?array $conceptKeywords = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('concepts')]
    public ?LinkURI $conceptKeywordsURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('notes')]
    public ?string $notes = null;

    /** @var string[]|null */
    #[Groups(['create', 'update', 'view'])]
    #[Assert\All(new Assert\Type('string'))]
    #[Map('subject')]
    public ?array $subject = null;

    /** @var LinkURI[] */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: LinkURI::class)),
        uniqueItems: true,
        nullable: true,
    )]
    #[Assert\All(new Assert\Type(LinkURI::class))]
    #[Map('subjects')]
    public ?array $subjectURI = [];

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        description: 'Language in browser locale supported format (RFC 5646).',
        example: 'en',
    )]
    #[Map('language')]
    public ?string $language = null;

    /** @var string[]|string|null */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        description: 'The education level(s) that the item is intended',
        type: 'array',
        items: new OA\Items(
            type: 'string',
            enum: [
                'PK', 'KG', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12',
                'IT', 'PR', 'TK', 'AS', 'BA', 'PB', 'MD', 'PM', 'DO', 'PD', 'AE', 'PT', 'OT',
            ],
        ),
        example: ['09', '10', '11', '12'],
        nullable: true,
    )]
    #[Map('educationalAlignment')]
    public string|array|null $educationLevel = null;

    #[Groups(['create', 'update', 'view'])]
    #[SerializedName('CFItemTypeURI')]
    #[Map('itemType.uri')]
    public ?LinkURI $cfItemTypeURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('licence.uri')]
    public ?LinkURI $licenseURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    #[Map('statusStart')]
    public ?\DateTimeInterface $statusStartDate = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    #[Map('statusEnd')]
    public ?\DateTimeInterface $statusEndDate = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date-time',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The date is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The date is required', groups: ['view'])]
    #[Map('changedAt')]
    public ?\DateTimeInterface $lastChangeDateTime = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    #[Map('extensions')]
    public ?array $extensions = null;
}
