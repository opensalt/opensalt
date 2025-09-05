<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

class DefinitionDto
{
    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        nullable: true,
    )]
    public ?array $CFConcepts = null;

    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        nullable: true,
    )]
    public ?array $CFSubjects = null;

    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        nullable: true,
    )]
    public ?array $CFLicenses = null;

    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        nullable: true,
    )]
    public ?array $CFItemTypes = null;

    /** @var array */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        nullable: true,
    )]
    public ?array $CFAssociationGroupings = null;
}
