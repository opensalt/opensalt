<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'CourseRef',
    description: 'Compact course reference used in requirements, paths, and receiving targets',
)]
final class CourseRefDto
{
    public function __construct(
        #[OA\Property(
            description: 'Human-readable course code (`humanCodingScheme`)',
            example: 'MATH 10',
        )]
        public string $courseCode = '',
        #[OA\Property(
            description: 'CASE item UUID',
            example: '60fd46d1-9c55-51bb-822a-f204d8512cc2',
        )]
        public string $identifier = '',
        #[OA\Property(
            description: 'Resolved CASE item URI',
            example: 'https://opensalt.example/uri/60fd46d1-9c55-51bb-822a-f204d8512cc2',
        )]
        public string $uri = '',
    ) {
    }
}
