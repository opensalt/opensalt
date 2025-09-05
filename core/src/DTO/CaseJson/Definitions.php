<?php

declare(strict_types=1);

namespace App\DTO\CaseJson;

use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use OpenApi\Attributes as OA;

#[OA\Schema()]
class Definitions
{
    /**
     * @var LsDefAssociationGrouping[]
     */
    #[OA\Property(
        description: 'Array of association groupings',
        type: 'array',
        nullable: true,
    )]
    public ?array $associationGroupings = [];

    /**
     * @var LsDefConcept[]
     */
    #[OA\Property(
        description: 'Array of concepts',
        type: 'array',
        nullable: true,
    )]
    public ?array $concepts = [];

    /**
     * @var LsDefItemType[]
     */
    #[OA\Property(
        description: 'Array of item types',
        type: 'array',
        nullable: true,
    )]
    public ?array $itemTypes = [];

    /**
     * @var LsDefLicence[]
     */
    #[OA\Property(
        description: 'Array of licenses',
        type: 'array',
        nullable: true,
    )]
    public ?array $licences = [];

    /**
     * @var LsDefSubject[]
     */
    #[OA\Property(
        description: 'Array of subjects',
        type: 'array',
        nullable: true,
    )]
    public ?array $subjects = [];

    #[OA\Property(
        description: 'Additional information about the definitions as key-value pairs',
        type: 'object',
        additionalProperties: true,
        nullable: true,
    )]
    public ?array $extensions = null;
}
