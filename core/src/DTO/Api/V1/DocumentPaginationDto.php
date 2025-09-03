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
        property: 'page[size]',
        description: 'Maximum number of documents to return (1-1000)',
        type: 'integer',
        default: 100,
        maximum: 1000,
        minimum: 1
    )]
    public int $size = 100 {
        get { return max(1, min(1000, $this->size ?: 100)); }
    }

    #[OA\Property(
        property: 'page[after]',
        description: 'Return documents after cursor',
        type: 'string',
        nullable: true
    )]
    public ?string $after = null;

    public function encodeCursor(string $sortValue, string $identifier, ?string $title = null): string
    {
        $data = $sortValue . '|' . $identifier;
        if (null !== $title) {
            $data .= '|' . $title;
        }

        return base64_encode($data);
    }

    /**
     * @return array{sortValue: string, identifier: string, title?: string}
     */
    public function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('Invalid cursor format');
        }

        $parts = explode('|', $decoded);
        if (count($parts) < 2 || count($parts) > 3) {
            throw new \InvalidArgumentException('Invalid cursor format');
        }

        $result = [
            'sortValue' => $parts[0],
            'identifier' => $parts[1],
        ];
        if (isset($parts[2])) {
            $result['title'] = $parts[2];
        }

        return $result;
    }
}
