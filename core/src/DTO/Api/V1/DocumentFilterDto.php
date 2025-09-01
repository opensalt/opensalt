<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'DocumentFilter',
    description: 'Filtering parameters for document queries'
)]
class DocumentFilterDto
{
    #[OA\Property(
        description: 'Filter by creator name (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $creator = null;

    #[OA\Property(
        description: 'Filter by title (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $title = null;

    #[OA\Property(
        description: 'Filter by adoption status',
        type: 'string',
        enum: ['Private Draft', 'Draft', 'Adopted', 'Deprecated'],
        nullable: true
    )]
    public ?string $adoptionStatus = null;

    #[OA\Property(
        description: 'Filter by subject (exact match)',
        type: 'string',
        nullable: true
    )]
    public ?string $subject = null;

    #[OA\Property(
        description: 'Filter by language code (e.g., "en", "fr")',
        type: 'string',
        nullable: true
    )]
    public ?string $language = null;

    #[OA\Property(
        description: 'Filter by CASE version',
        type: 'string',
        enum: ['1.1'],
        nullable: true
    )]
    public ?string $caseVersion = null;

    #[OA\Property(
        description: 'Filter by publisher (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $publisher = null;

    #[Assert\Choice(['updatedAt', 'title', 'identifier', 'lastChangeDateTime'])]
    #[OA\Property(
        description: 'Sort field',
        type: 'string',
        default: 'updatedAt',
        enum: ['updatedAt', 'title', 'identifier', 'lastChangeDateTime']
    )]
    public string $sort = 'updatedAt';

    #[Assert\Choice(['asc', 'desc'])]
    #[OA\Property(
        description: 'Sort direction',
        type: 'string',
        default: 'desc',
        enum: ['asc', 'desc']
    )]
    public string $order = 'desc';

    public function getSort(): string
    {
        return match ($this->sort) {
            'title' => 'd.title',
            'identifier' => 'd.identifier',
            'lastChangeDateTime' => 'd.changedAt',
            default => 'd.updatedAt',
        };
    }

    public function getOrder(): string
    {
        return 'ASC' === strtoupper($this->order) ? 'ASC' : 'DESC';
    }

    public function hasFilters(): bool
    {
        return null !== $this->creator
            || null !== $this->title
            || null !== $this->adoptionStatus
            || null !== $this->subject
            || null !== $this->language
            || null !== $this->caseVersion
            || null !== $this->publisher;
    }
}
