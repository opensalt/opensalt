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
        'lastChangeDateTime',
    ]
)]
class CFRubricCriterion
{
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
    )]
    public UuidInterface $identifier;

    #[OA\Property(
        type: 'string',
        format: 'uri',
    )]
    public string $uri;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $category = null;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[OA\Property(ref: new Model(type: LinkURI::class), nullable: true)]
    #[SerializedName('CFItemURI')]
    public ?LinkURI $cfItemURI = null;

    #[OA\Property(
        type: 'number',
        format: 'float',
        nullable: true,
    )]
    public ?float $weight = null;

    #[OA\Property(
        type: 'integer',
        nullable: true,
    )]
    public ?int $position = null;

    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
        nullable: true,
    )]
    public ?string $rubricId = null;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    /**
     * @var CFRubricCriterionLevel[]|null
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFRubricCriterionLevel::class)),
        nullable: true,
    )]
    #[SerializedName('CFRubricCriterionLevels')]
    public ?array $cfRubricCriterionLevels = [];

    #[OA\Property(
        description: 'Additional information about the rubric criterion as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
