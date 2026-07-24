<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Paths',
    description: <<<'DESC'
Flattened DNF (OR-of-AND-lists) view of the requirement tree for checklist UIs.
Derived from the same tree used for evaluation. Option count is capped (default 32);
when the cap is hit, `truncated` is true.
DESC
    ,
    example: [
        'truncated' => false,
        'options' => [
            [
                'status' => 'satisfied',
                'require' => [
                    ['courseCode' => 'MATH 10', 'identifier' => '…', 'uri' => '…'],
                ],
            ],
            [
                'status' => 'unsatisfied',
                'require' => [
                    ['courseCode' => 'SOC 7', 'identifier' => '…', 'uri' => '…'],
                ],
            ],
        ],
    ],
)]
final class PathsDto
{
    public function __construct(
        #[OA\Property(
            description: 'True if more DNF options exist than the server cap (some options omitted)',
            example: false,
        )]
        public bool $truncated = false,
        /** @var list<PathOptionDto> */
        #[OA\Property(
            description: 'Alternative paths; each is an AND-list of required courses',
            type: 'array',
            items: new OA\Items(ref: new Model(type: PathOptionDto::class)),
        )]
        public array $options = [],
    ) {
    }
}
