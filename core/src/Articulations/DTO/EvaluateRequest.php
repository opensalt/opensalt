<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use App\Articulations\Model\EntityIdentifiers;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'EvaluateRequest',
    description: <<<'DESC'
Request body for articulation evaluation.

The service finds the articulation framework for the sending → receiving institution pair,
then evaluates each `sendingCourses` entry against that package’s articulation rules.

Returns only articulations that are **satisfied** or **partial**.
DESC
    ,
    required: ['sendingInstitution', 'receivingInstitution', 'sendingCourses'],
    example: [
        'sendingInstitution' => [
            'identifiers' => [
                ['type' => 'coci:schoolId', 'value' => '46'],
                ['type' => 'codes:etsCode', 'value' => '4315'],
            ],
        ],
        'receivingInstitution' => [
            'identifiers' => [
                ['type' => 'identifier', 'value' => '833e47a4-a238-5af4-ab79-c6df319df869'],
                ['type' => 'ais:institutionId', 'value' => '39'],
            ],
        ],
        'sendingCourses' => [
            [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'MATH 10'],
                ],
            ],
            [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'CHEM 12A'],
                    ['type' => 'coci:courseId', 'value' => '12345'],
                ],
            ],
        ],
    ],
)]
final class EvaluateRequest
{
    #[Assert\NotNull]
    #[Assert\Valid]
    #[OA\Property(
        description: 'College / institution that awarded the sending courses (e.g. community college)',
        ref: new Model(type: EntityRefDto::class),
        example: [
            'identifiers' => [
                ['type' => 'coci:schoolId', 'value' => '46'],
                ['type' => 'codes:etsCode', 'value' => '4315'],
            ],
        ],
    )]
    public EntityRefDto $sendingInstitution;

    #[Assert\NotNull]
    #[Assert\Valid]
    #[OA\Property(
        description: 'Institution where credit is sought (e.g. CSU / university)',
        ref: new Model(type: EntityRefDto::class),
        example: [
            'identifiers' => [
                ['type' => 'identifier', 'value' => '833e47a4-a238-5af4-ab79-c6df319df869'],
                ['type' => 'ais:institutionId', 'value' => '39'],
            ],
        ],
    )]
    public EntityRefDto $receivingInstitution;

    /** @var list<EntityRefDto> */
    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\Count(max: 50, maxMessage: 'Too many sending courses ({{ limit }} max).')]
    #[Assert\Valid]
    #[OA\Property(
        description: 'Courses the student has completed at the sending institution. Empty array is allowed and yields `results: []`.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: EntityRefDto::class)),
        example: [
            [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'MATH 10'],
                ],
            ],
            [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'PSYC 7'],
                ],
            ],
        ],
    )]
    public array $sendingCourses = [];

    /**
     * @return list<EntityIdentifiers>
     */
    public function mapSendingCourses(): array
    {
        return array_map(
            static fn (EntityRefDto $course): EntityIdentifiers => $course->toEntityIdentifiers(),
            $this->sendingCourses,
        );
    }
}
