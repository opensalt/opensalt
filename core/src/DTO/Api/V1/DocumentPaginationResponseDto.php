<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'DocumentPaginationResponse',
    description: 'Pagination response metadata for document queries'
)]
class DocumentPaginationResponseDto
{
    #[OA\Property(
        description: 'Whether there is a next page available',
        type: 'boolean'
    )]
    public bool $hasNextPage;

    #[OA\Property(
        description: 'Whether there is a previous page available',
        type: 'boolean'
    )]
    public bool $hasPrevPage;

    #[OA\Property(
        description: 'Cursor for the next page',
        type: 'string',
        nullable: true
    )]
    public ?string $nextCursor;

    #[OA\Property(
        description: 'Cursor for the previous page',
        type: 'string',
        nullable: true
    )]
    public ?string $prevCursor;

    #[OA\Property(
        description: 'Total number of items',
        type: 'integer'
    )]
    public int $total;

    public function __construct(bool $hasNextPage, bool $hasPrevPage, ?string $nextCursor, ?string $prevCursor, int $total)
    {
        $this->hasNextPage = $hasNextPage;
        $this->hasPrevPage = $hasPrevPage;
        $this->nextCursor = $nextCursor;
        $this->prevCursor = $prevCursor;
        $this->total = $total;
    }
}
