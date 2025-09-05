<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[OA\Schema()]
class CFDefinition
{
    /**
     * @var CFConcept[]|null
     */
    #[OA\Property(
        description: 'Array of concepts',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFConcept::class)),
        nullable: true,
    )]
    #[SerializedName('CFConcepts')]
    public ?array $cfConcepts = [];

    /**
     * @var CFSubject[]|null
     */
    #[OA\Property(
        description: 'Array of subjects',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFSubject::class)),
        nullable: true,
    )]
    #[SerializedName('CFSubjects')]
    public ?array $cfSubjects = [];

    /**
     * @var CFLicense[]|null
     */
    #[OA\Property(
        description: 'Array of licenses',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFLicense::class)),
        nullable: true,
    )]
    #[SerializedName('CFLicenses')]
    public ?array $cfLicenses = [];

    /**
     * @var CFItemType[]|null
     */
    #[OA\Property(
        description: 'Array of item types',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFItemType::class)),
        nullable: true,
    )]
    #[SerializedName('CFItemTypes')]
    public ?array $cfItemTypes = [];

    /**
     * @var CFAssociationGrouping[]|null
     */
    #[OA\Property(
        description: 'Array of association groupings',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFAssociationGrouping::class)),
        nullable: true,
    )]
    #[SerializedName('CFAssociationGroupings')]
    public ?array $cfAssociationGroupings = [];

    #[OA\Property(
        description: 'Additional information about the definitions as key-value pairs',
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
