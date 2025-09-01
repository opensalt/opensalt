<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class PatchDto
{
    /**
     * @var PatchOperation[]|null
     */
    #[Assert\All([
        new Assert\Type(PatchOperation::class),
    ])]
    #[OA\Property(
        description: 'Array of JSON Patch operations to apply',
        type: 'array',
        items: new OA\Items(type: PatchOperation::class),
    )]
    public ?array $patch = null;
}
