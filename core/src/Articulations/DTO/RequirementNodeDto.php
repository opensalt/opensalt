<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'RequirementNode',
    description: <<<'DESC'
Recursive sending-side requirement tree (source of truth for evaluation).

**Group / set node** — has `op`:
- `anyOf` — OR of members (CASE Requirement Set)
- `allOf` — AND of members (CASE Requirement Group)

**Course leaf** — has `courseCode` / `identifier` / `uri` and `match` (true if a request sending course matched).

`status` on group nodes: `satisfied`, `partial`, or `unsatisfied`.
DESC
    ,
    example: [
        'op' => 'anyOf',
        'status' => 'satisfied',
        'members' => [
            [
                'courseCode' => 'MATH 10',
                'identifier' => '60fd46d1-9c55-51bb-822a-f204d8512cc2',
                'uri' => 'local:60fd46d1-9c55-51bb-822a-f204d8512cc2',
                'match' => true,
            ],
            [
                'courseCode' => 'SOC 7',
                'identifier' => '859ba9ee-4174-5294-9c23-e51aeae956a6',
                'uri' => 'local:859ba9ee-4174-5294-9c23-e51aeae956a6',
                'match' => false,
            ],
        ],
    ],
)]
final class RequirementNodeDto
{
    public function __construct(
        #[OA\Property(
            description: 'Present on group/set nodes only',
            enum: ['allOf', 'anyOf'],
            example: 'anyOf',
        )]
        public ?string $op = null,
        #[OA\Property(
            description: 'Evaluation status for this node',
            enum: ['satisfied', 'partial', 'unsatisfied'],
            example: 'satisfied',
        )]
        public ?string $status = null,
        /** @var list<RequirementNodeDto> */
        #[OA\Property(
            description: 'Child nodes when `op` is present',
            type: 'array',
            items: new OA\Items(ref: new Model(type: RequirementNodeDto::class)),
        )]
        public array $members = [],
        #[OA\Property(
            description: 'Present on course leaf nodes',
            example: 'MATH 10',
        )]
        public ?string $courseCode = null,
        #[OA\Property(description: 'CASE item UUID (course leaf)')]
        public ?string $identifier = null,
        #[OA\Property(description: 'CASE item URI (course leaf)')]
        public ?string $uri = null,
        #[OA\Property(
            description: 'Whether this leaf matched a request sending course (course leaves only)',
            example: true,
        )]
        public ?bool $match = null,
    ) {
    }
}
