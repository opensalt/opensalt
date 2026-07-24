<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'SeriesRef',
    description: 'Receiving-side series: an AND of multiple receiving courses (CASE Series item)',
    example: [
        'conjunction' => 'and',
        'courseCode' => 'CHEM 112A+112B+113A',
        'identifier' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        'uri' => 'local:a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        'courses' => [
            ['courseCode' => 'CHEM 112A', 'identifier' => '…', 'uri' => '…'],
            ['courseCode' => 'CHEM 112B', 'identifier' => '…', 'uri' => '…'],
            ['courseCode' => 'CHEM 113A', 'identifier' => '…', 'uri' => '…'],
        ],
    ],
)]
final class SeriesRefDto
{
    public function __construct(
        #[OA\Property(
            description: 'How series member courses combine (member courses are ANDed together)',
            example: 'and',
        )]
        public string $conjunction = 'and',
        #[OA\Property(
            description: 'Composite coding scheme for the series',
            example: 'CHEM 112A+112B+113A',
        )]
        public string $courseCode = '',
        #[OA\Property(description: 'CASE Series item UUID')]
        public string $identifier = '',
        #[OA\Property(description: 'CASE Series item URI')]
        public string $uri = '',
        /** @var list<CourseRefDto> */
        #[OA\Property(
            description: 'Member courses of the series',
            type: 'array',
            items: new OA\Items(ref: new Model(type: CourseRefDto::class)),
        )]
        public array $courses = [],
    ) {
    }
}
