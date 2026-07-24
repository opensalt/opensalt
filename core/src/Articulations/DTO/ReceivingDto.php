<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Receiving',
    description: <<<'DESC'
Receiving (destination) side of an articulation. Exactly one of `course` or `series` is set:

- `course` — maps to a single receiving course
- `series` — maps to a multi-course receiving series (AND of courses)
DESC
)]
final class ReceivingDto
{
    public function __construct(
        #[OA\Property(
            description: 'Single receiving course (mutually exclusive with `series`)',
            ref: new Model(type: CourseRefDto::class),
        )]
        public ?CourseRefDto $course = null,
        #[OA\Property(
            description: 'Receiving series of courses (mutually exclusive with `course`)',
            ref: new Model(type: SeriesRefDto::class),
        )]
        public ?SeriesRefDto $series = null,
    ) {
    }
}
