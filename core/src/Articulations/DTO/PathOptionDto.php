<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'PathOption',
    description: 'One disjunctive-normal-form option: an AND-list of courses the student needs for this alternative',
    example: [
        'status' => 'satisfied',
        'require' => [
            [
                'courseCode' => 'MATH 10',
                'identifier' => '60fd46d1-9c55-51bb-822a-f204d8512cc2',
                'uri' => 'local:60fd46d1-9c55-51bb-822a-f204d8512cc2',
            ],
        ],
    ],
)]
final class PathOptionDto
{
    public function __construct(
        #[OA\Property(
            description: 'Whether this option is fully satisfied given the request courses',
            enum: ['satisfied', 'partial', 'unsatisfied'],
            example: 'satisfied',
        )]
        public string $status = '',
        /** @var list<CourseRefDto> */
        #[OA\Property(
            description: 'Courses required together for this option (AND)',
            type: 'array',
            items: new OA\Items(ref: new Model(type: CourseRefDto::class)),
        )]
        public array $require = [],
    ) {
    }
}
