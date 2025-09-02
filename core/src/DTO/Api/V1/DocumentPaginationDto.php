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
        description: 'Maximum number of documents to return (1-1000)',
        type: 'integer',
        default: 100,
        maximum: 1000,
        minimum: 1
    )]
    public int $limit = 100 {
        get { return max(1, min(1000, $this->limit ?: 100)); }
    }

    #[OA\Property(
        description: 'Cursor for pagination (document identifier)',
        type: 'string',
        nullable: true
    )]
    public ?string $cursor = null {
        get {
            if (null === $this->cursor) {
                return null;
            }
            $decoded = base64_decode($this->cursor, true);
            if (false === $decoded) {
                return null;
            }

            return $decoded;
        }
        set(?string $value) {
            if (null === $value) {
                $this->cursor = null;
            } else {
                $this->cursor = base64_encode($value);
            }
        }
    }

    #[Assert\Choice(['next', 'prev'])]
    #[OA\Property(
        description: 'Pagination direction',
        type: 'string',
        default: 'next',
        enum: ['next', 'prev']
    )]
    public string $direction = 'next';

    public function encodeCursor(string $identifier): string
    {
        return base64_encode($identifier);
    }
}
