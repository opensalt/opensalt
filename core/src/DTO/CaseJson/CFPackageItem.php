<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

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

    public string $fullStatement;

    public ?string $alternativeLabel = null;

    #[SerializedName('CFItemType')]
    public ?string $cfItemType = null;

    public ?string $humanCodingScheme = null;
    public ?string $listEnumeration = null;
    public ?string $abbreviatedStatement = null;

    /** @var string[]|array|null */
    public ?array $conceptKeywords = null;

    public ?LinkURI $conceptKeywordsURI = null;
    public ?string $notes = null;

    /** @var string[]|string|null */
    public string|array|null $subject = null;

    /** @var LinkURI[] */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: LinkURI::class)),
    )]
    public ?array $subjectURI = [];

    public ?string $language = null;

    /** @var string[]|string|null */
    public string|array|null $educationLevel = null;

    #[SerializedName('CFItemTypeURI')]
    public ?LinkURI $cfItemTypeURI = null;
    public ?LinkURI $licenseURI = null;

    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    public ?\DateTimeInterface $statusStartDate = null;

    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    public ?\DateTimeInterface $statusEndDate = null;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    public ?array $extensions = null;
}
