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
        'lastChangeDateTime',
    ]
)]
class CFRubric
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
    public ?string $title = null;

    #[OA\Property(
        type: 'string',
        nullable: true,
    )]
    public ?string $description = null;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
    )]
    public \DateTimeInterface $lastChangeDateTime;

    /**
     * @var CFRubricCriterion[]|null
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFRubricCriterion::class)),
        nullable: true,
    )]
    #[SerializedName('CFRubricCriteria')]
    public ?array $cfRubricCriteria = [];

    #[OA\Property(
        description: 'Additional information about the rubric as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
