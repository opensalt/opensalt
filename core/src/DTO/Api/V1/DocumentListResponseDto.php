<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'DocumentListResponse',
    description: 'Paginated response containing a list of documents'
)]
class DocumentListResponseDto
{
    /** @var DocumentDto[] */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/DocumentDto'),
        description: 'Array of documents'
    )]
    public array $data;

    #[OA\Property(
        ref: '#/components/schemas/DocumentPaginationResponseDto',
        description: 'Pagination metadata'
    )]
    public DocumentPaginationResponseDto $pagination;

    /** @var array<string, mixed> */
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
        description: 'Links to related resources'
    )]
    public ?array $links = null;

    public function __construct(array $data, DocumentPaginationResponseDto $pagination, ?array $links = null)
    {
        $this->data = $data;
        $this->pagination = $pagination;
        $this->links = $links;
    }
}
