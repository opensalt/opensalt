<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use Nelmio\ApiDocBundle\Attribute\Ignore;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: 'DocumentFilter',
    description: 'Filtering parameters for document queries'
)]
class DocumentFilterDto
{
    #[OA\Property(
        property: 'filter[title]',
        description: 'Filter by title (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $title = null;

    #[OA\Property(
        property: 'filter[creator]',
        description: 'Filter by creator name (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $creator = null;

    #[OA\Property(
        property: 'filter[publisher]',
        description: 'Filter by publisher (partial match)',
        type: 'string',
        nullable: true
    )]
    public ?string $publisher = null;

    #[Ignore]
    #[OA\Property(
        property: 'filter[adoptionStatus]',
        description: 'Filter by adoption status',
        type: 'string',
        // enum: ['Private Draft', 'Draft', 'Adopted', 'Deprecated'],
        nullable: true
    )]
    public ?string $adoptionStatus = null;

    #[Ignore]
    #[OA\Property(
        property: 'filter[subject]',
        description: 'Filter by subject (exact match)',
        type: 'string',
        nullable: true
    )]
    public ?string $subject = null;

    #[Ignore]
    #[OA\Property(
        property: 'filter[language]',
        description: 'Filter by language code (e.g., "en", "fr", "en-US")',
        type: 'string',
        nullable: true
    )]
    public ?string $language = null;

    #[Assert\Choice(choices: ['title', 'creator', 'identifier', 'lastChangeDateTime'])]
    #[OA\Property(
        property: 'filter[sort]',
        description: 'Sort field',
        type: 'string',
        default: 'identifier',
        enum: ['title', 'creator', 'identifier', 'lastChangeDateTime']
    )]
    public string $sort = 'identifier';

    #[Ignore]
    public string $sortField {
        get {
            return match ($this->sort) {
                'title' => 'd.title',
                'creator' => 'd.creator',
                'identifier' => 'd.identifier',
                'lastChangeDateTime' => 'd.changedAt',
                default => 'd.identifier',
            };
        }
    }

    #[Assert\Choice(choices: ['asc', 'desc'])]
    #[OA\Property(
        property: 'filter[order]',
        description: 'Sort direction',
        type: 'string',
        default: 'asc',
        enum: ['asc', 'desc']
    )]
    public string $order = 'asc';

    #[Ignore]
    public string $sortOrder {
        get {
            return 'DESC' === strtoupper($this->order) ? 'DESC' : 'ASC';
        }
    }

    #[Ignore]
    public function hasFilters(): bool
    {
        return null !== $this->creator
            || null !== $this->title
            || null !== $this->adoptionStatus
            || null !== $this->subject
            || null !== $this->language
            || null !== $this->publisher;
    }
}
