<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'DocumentPagination',
    description: 'Cursor-based pagination parameters for document queries'
)]
class DocumentPaginationDto
{
    #[Assert\Range(min: 1, max: 1000)]
    #[OA\Property(
        description: 'Maximum number of documents to return (1-100)',
        type: 'integer',
        default: 100,
        maximum: 1000,
        minimum: 1
    )]
    public int $limit = 100;

    #[OA\Property(
        description: 'Cursor for pagination (base64 encoded document ID)',
        type: 'string',
        nullable: true
    )]
    public ?string $cursor = null;

    #[Assert\Choice(['next', 'prev'])]
    #[OA\Property(
        description: 'Pagination direction',
        type: 'string',
        default: 'next',
        enum: ['next', 'prev']
    )]
    public string $direction = 'next';

    public function getLimit(): int
    {
        return max(1, min(100, $this->limit));
    }

    public function getCursor(): ?int
    {
        if (null === $this->cursor) {
            return null;
        }

        // Decode base64 cursor to get the document ID
        $decoded = base64_decode($this->cursor, true);
        if (false === $decoded) {
            return null;
        }

        $id = (int) $decoded;

        return $id > 0 ? $id : null;
    }

    public function encodeCursor(int $id): string
    {
        return base64_encode((string) $id);
    }
}
