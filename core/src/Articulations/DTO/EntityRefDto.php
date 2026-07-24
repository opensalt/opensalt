<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\Identifier;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'EntityRef',
    description: <<<'DESC'
An institution or course referenced by one or more typed identifiers.
Provide the most specific / trusted identifiers first; the service uses **first match wins**.
At least one identifier is required.
DESC
    ,
    example: [
        'identifiers' => [
            ['type' => 'coci:schoolId', 'value' => '46'],
            ['type' => 'codes:etsCode', 'value' => '4315'],
            ['type' => 'codes:misCode', 'value' => '422'],
        ],
    ],
)]
final class EntityRefDto
{
    /** @var list<IdentifierDto> */
    #[Assert\Count(min: 1, max: 10, maxMessage: 'Too many identifiers ({{ limit }} max).')]
    #[Assert\Valid]
    #[Assert\NotNull]
    #[OA\Property(
        description: 'Ordered list of identifiers; scanned in order until one matches',
        type: 'array',
        minItems: 1,
        items: new OA\Items(ref: new Model(type: IdentifierDto::class)),
        example: [
            ['type' => 'coci:schoolId', 'value' => '46'],
            ['type' => 'codes:etsCode', 'value' => '4315'],
        ],
    )]
    public array $identifiers = [];

    public function toEntityIdentifiers(): EntityIdentifiers
    {
        return new EntityIdentifiers(
            array_map(
                static fn (IdentifierDto $dto): Identifier => new Identifier($dto->type, $dto->value),
                $this->identifiers,
            ),
        );
    }
}
