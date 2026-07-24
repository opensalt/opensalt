<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'EvaluateResponse',
    description: <<<'DESC'
Successful evaluation payload.

Returns resolved sending/receiving institutions (matched identifiers and display name),
identifies which articulation CFDocument / academic year was selected, and lists only
articulations with status `satisfied` or `partial`.
Fully unmatched receiving targets are omitted (`results` may be an empty array).
DESC
    ,
    required: ['sendingInstitution', 'receivingInstitution', 'articulationDocument', 'results'],
    example: [
        'sendingInstitution' => [
            'name' => 'Foothill College',
            'identifiers' => [
                ['type' => 'coci:schoolId', 'value' => '46'],
                ['type' => 'identifier', 'value' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'],
            ],
        ],
        'receivingInstitution' => [
            'name' => 'San José State University',
            'identifiers' => [
                ['type' => 'identifier', 'value' => '833e47a4-a238-5af4-ab79-c6df319df869'],
            ],
        ],
        'articulationDocument' => [
            'identifier' => '94f8443e-8a60-5d8a-b7fb-ca0e48299774',
            'uri' => 'https://opensalt.example/uri/94f8443e-8a60-5d8a-b7fb-ca0e48299774',
        ],
        'academicYear' => '2025-2026',
        'results' => [
            [
                'status' => 'satisfied',
                'articulationKey' => '76/51/to/39/Department/8047:2',
                'receiving' => [
                    'course' => [
                        'courseCode' => 'JS 15',
                        'identifier' => 'a1855e67-f517-53bf-89ff-b189ec38e6d8',
                        'uri' => 'https://opensalt.example/uri/a1855e67-f517-53bf-89ff-b189ec38e6d8',
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
        ],
    ],
)]
final class EvaluateResponse
{
    public function __construct(
        #[OA\Property(
            description: 'Resolved sending institution',
            ref: new Model(type: InstitutionRefDto::class),
        )]
        public InstitutionRefDto $sendingInstitution = new InstitutionRefDto(),
        #[OA\Property(
            description: 'Resolved receiving institution',
            ref: new Model(type: InstitutionRefDto::class),
        )]
        public InstitutionRefDto $receivingInstitution = new InstitutionRefDto(),
        #[OA\Property(
            description: 'Articulation CFDocument that was resolved and evaluated',
            ref: new Model(type: DocumentRefDto::class),
        )]
        public DocumentRefDto $articulationDocument = new DocumentRefDto(),
        #[OA\Property(
            description: 'Academic year code from the selected document, when present',
            example: '2025-2026',
            nullable: true,
        )]
        public ?string $academicYear = null,
        /** @var list<EvaluateResultDto> */
        #[OA\Property(
            description: 'Matched articulations only (`satisfied` or `partial`). Empty when nothing matched.',
            type: 'array',
            items: new OA\Items(ref: new Model(type: EvaluateResultDto::class)),
        )]
        public array $results = [],
    ) {
    }
}
