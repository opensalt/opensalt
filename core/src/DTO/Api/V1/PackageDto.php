<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PackageDto
{
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The document must be in the package', groups: ['create', 'update', 'view'])]
    public ?DocumentDto $CFDocument = null;

    /** @var ItemDto[] */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: ItemDto::class)),
        nullable: true,
    )]
    #[Assert\All(new Assert\Type(ItemDto::class), groups: ['create', 'update', 'view'])]
    public ?array $CFItems = null;

    /** @var AssociationDto[] */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: AssociationDto::class)),
        nullable: true,
    )]
    #[Assert\All(new Assert\Type(AssociationDto::class), groups: ['create', 'update', 'view'])]
    public ?array $CFAssociations = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
    )]
    public ?DefinitionDto $CFDefinitions = null;

    /** @var RubricDto[] */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: RubricDto::class)),
        nullable: true,
    )]
    #[Assert\All(new Assert\Type(RubricDto::class, groups: ['create', 'update', 'view']))]
    public ?array $CFRubrics = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    public ?array $extensions = null;
}
