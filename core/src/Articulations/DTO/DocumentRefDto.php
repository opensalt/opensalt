<?php

declare(strict_types=1);

namespace App\Articulations\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'DocumentRef',
    description: 'CASE CFDocument that was selected for evaluation (echoed so clients know which package was used)',
)]
final class DocumentRefDto
{
    public function __construct(
        #[OA\Property(
            description: 'CASE / OpenSALT document UUID',
            example: '94f8443e-8a60-5d8a-b7fb-ca0e48299774',
        )]
        public string $identifier = '',
        #[OA\Property(
            description: 'Resolved CASE document URI',
            example: 'https://opensalt.example/uri/94f8443e-8a60-5d8a-b7fb-ca0e48299774',
        )]
        public string $uri = '',
    ) {
    }
}
