<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'InstitutionRef',
    description: 'Resolved institution in an evaluate response: display name plus the matched identifier(s).',
    required: ['identifiers'],
    example: [
        'name' => 'Foothill College',
        'identifiers' => [
            ['type' => 'coci:schoolId', 'value' => '46'],
            ['type' => 'identifier', 'value' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'],
        ],
    ],
)]
final class InstitutionRefDto
{
    #[OA\Property(
        description: 'Name of the organization (e.g. "Foothill College")',
        example: 'Foothill College',
        nullable: true,
    )]
    public ?string $name = null;

    /** @var list<IdentifierDto> */
    #[OA\Property(
        description: 'The request identifier that matched, plus the CASE `identifier` UUID when the match was on another type',
        type: 'array',
        items: new OA\Items(ref: new Model(type: IdentifierDto::class)),
    )]
    public array $identifiers = [];
}
