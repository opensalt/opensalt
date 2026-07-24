<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'Identifier',
    description: <<<'DESC'
One typed identifier for an institution or course. The service tries identifiers in array order; **first match wins**.

**Built-in `type` values (courses):**
- `identifier` — CASE/OpenSALT UUID on the CFItem
- `uri` — CASE/OpenSALT URI on the CFItem
- `courseCode` — ignores whitespace, trailing `.`, and leading zeros in numeric runs

**Built-in `type` values (institutions):** `identifier`, `uri`
DESC
)]
final class IdentifierDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        #[OA\Property(
            description: 'Identifier scheme / coding system (e.g. coci:schoolId, codes:etsCode, codes:misCode, courseCode, coci:courseId, identifier, uri)',
            example: 'coci:schoolId',
        )]
        public string $type = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 256)]
        #[OA\Property(
            description: 'Identifier value in that scheme (e.g. 46, 4315, MATH 10)',
            example: '46',
        )]
        public string $value = '',
    ) {
    }
}
