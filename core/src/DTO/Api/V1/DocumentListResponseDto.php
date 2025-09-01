<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'DocumentListResponse',
    description: 'Paginated response containing a list of documents'
)]
class DocumentListResponseDto
{
    /** @var DocumentDto[] */
    #[OA\Property(
        description: 'Array of documents',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/DocumentDto')
    )]
    public array $data;

    #[OA\Property(
        ref: new Model(type: DocumentPaginationResponseDto::class),
        description: 'Pagination metadata',
    )]
    public DocumentPaginationResponseDto $pagination;

    /** @var array<string, mixed> */
    #[OA\Property(
        description: 'Links to related resources',
        type: 'object',
        nullable: true,
        additionalProperties: true
    )]
    public ?array $links = null;

    public function __construct(array $data, DocumentPaginationResponseDto $pagination, ?array $links = null)
    {
        $this->data = $data;
        $this->pagination = $pagination;
        $this->links = $links;
    }
}
