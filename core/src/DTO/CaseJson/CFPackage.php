<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[OA\Schema(
    required: [
        'CFDocument',
    ]
)]
class CFPackage
{
    #[OA\Property(
        ref: new Model(type: CFPackageDocument::class),
        description: 'The document containing metadata about the package'
    )]
    #[SerializedName('CFDocument')]
    public CFPackageDocument $cfDocument;

    /**
     * @var CFPackageItem[]|null
     */
    #[OA\Property(
        description: 'Array of items in the package',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFPackageItem::class)),
        nullable: true,
    )]
    #[SerializedName('CFItems')]
    public ?array $cfItems = [];

    /**
     * @var CFPackageAssociation[]|null
     */
    #[OA\Property(
        description: 'Array of associations between items in the package',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFPackageAssociation::class)),
        nullable: true,
    )]
    #[SerializedName('CFAssociations')]
    public ?array $cfAssociations = [];

    #[OA\Property(
        ref: new Model(type: CFDefinition::class),
        description: 'Definitions for concepts, subjects, licenses, and item types used in the package',
        nullable: true
    )]
    #[SerializedName('CFDefinitions')]
    public ?CFDefinition $cfDefinitions = null;

    /**
     * @var CFRubric[]|null
     */
    #[OA\Property(
        description: 'Array of rubrics in the package',
        type: 'array',
        items: new OA\Items(ref: new Model(type: CFRubric::class)),
        nullable: true,
    )]
    #[SerializedName('CFRubrics')]
    public ?array $cfRubrics = [];

    #[OA\Property(
        description: 'Additional information about the package as key-value pairs',
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
