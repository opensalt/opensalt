<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'PatchOperation',
    description: 'A JSON Patch operation as defined in RFC 6902'
)]
class PatchOperation
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['add', 'replace', 'remove', 'move', 'copy', 'test'])]
    #[OA\Property(
        description: 'The operation to perform',
        type: 'string',
        enum: ['add', 'replace', 'remove', 'move', 'copy', 'test']
    )]
    public string $op;

    #[Assert\NotBlank]
    #[OA\Property(
        description: 'A JSON Pointer path to the target location',
        type: 'string'
    )]
    public string $path;

    #[OA\Property(
        description: 'The value to add, replace, or test (not used for remove operation)',
        nullable: true
    )]
    public mixed $value = null;

    #[OA\Property(
        description: 'The JSON Pointer path to the source location (used for move and copy operations)',
        type: 'string',
        nullable: true
    )]
    public ?string $from = null;
}
