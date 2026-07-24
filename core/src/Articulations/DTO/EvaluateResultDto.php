<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'EvaluateResult',
    description: <<<'DESC'
One articulation that is at least partially matched by the request courses.

- `status` — overall result for this articulation (`satisfied` or `partial`)
- `receiving` — what receiving course or series this articulation grants
- `requirement` — recursive AND/OR tree of sending requirements (source of truth)
- `paths` — flattened DNF options for checklist-style UIs
DESC
    ,
    example: [
        'status' => 'satisfied',
        'articulationKey' => '76/51/to/39/Department/8047:2',
        'receiving' => [
            'course' => [
                'courseCode' => 'JS 15',
                'identifier' => 'a1855e67-f517-53bf-89ff-b189ec38e6d8',
                'uri' => 'local:a1855e67-f517-53bf-89ff-b189ec38e6d8',
            ],
        ],
        'requirement' => [
            'op' => 'anyOf',
            'status' => 'satisfied',
            'members' => [
                [
                    'courseCode' => 'MATH 10',
                    'identifier' => '…',
                    'uri' => '…',
                    'match' => true,
                ],
                [
                    'courseCode' => 'SOC 7',
                    'identifier' => '…',
                    'uri' => '…',
                    'match' => false,
                ],
            ],
        ],
        'paths' => [
            'truncated' => false,
            'options' => [
                [
                    'status' => 'satisfied',
                    'require' => [
                        ['courseCode' => 'MATH 10', 'identifier' => '…', 'uri' => '…'],
                    ],
                ],
            ],
        ],
    ],
)]
final class EvaluateResultDto
{
    public function __construct(
        #[OA\Property(
            description: 'Overall match status for this articulation (unsatisfied rows are omitted from the response)',
            enum: ['satisfied', 'partial'],
            example: 'satisfied',
        )]
        public string $status = '',
        #[OA\Property(
            description: 'Articulation key when present on the association',
            example: '76/51/to/39/Department/8047:2',
            nullable: true,
        )]
        public ?string $articulationKey = null,
        #[OA\Property(
            description: 'Receiving course or series granted by this articulation',
            ref: new Model(type: ReceivingDto::class),
        )]
        public ?ReceivingDto $receiving = null,
        #[OA\Property(
            description: 'Sending-side requirement tree with match/status annotations',
            ref: new Model(type: RequirementNodeDto::class),
        )]
        public ?RequirementNodeDto $requirement = null,
        #[OA\Property(
            description: 'DNF path view of the same requirement tree',
            ref: new Model(type: PathsDto::class),
        )]
        public ?PathsDto $paths = null,
    ) {
    }
}
