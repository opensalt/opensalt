<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;

readonly class OpenSaltMcpPayloadFactory
{
    /**
     * @return array{
     *     id: ?int,
     *     identifier: string,
     *     uri: string,
     *     title: ?string,
     *     creator: ?string,
     *     adoption_status: ?string,
     *     language: ?string,
     *     updated_at: string
     * }
     */
    public function documentSummary(LsDoc $document): array
    {
        return [
            'id' => $document->getId(),
            'identifier' => $document->getIdentifier(),
            'uri' => $document->getUri(),
            'title' => $document->getTitle(),
            'creator' => $document->getCreator(),
            'adoption_status' => $document->getAdoptionStatus(),
            'language' => $document->getLanguage(),
            'updated_at' => $this->formatDate($document->getUpdatedAt()),
        ];
    }

    /**
     * @return array{
     *     id: ?int,
     *     identifier: string,
     *     uri: string,
     *     title: ?string,
     *     creator: ?string,
     *     adoption_status: ?string,
     *     language: ?string,
     *     updated_at: string,
     *     changed_at: string,
     *     publisher: ?string,
     *     version: ?string,
     *     description: ?string,
     *     official_uri: ?string,
     *     status_start: ?string,
     *     status_end: ?string,
     *     url_name: ?string,
     *     is_mirrored: bool,
     *     mirrored_visible: ?bool,
     *     item_count: int,
     *     subjects: list<string>,
     *     subject_codes: list<string>
     * }
     */
    public function documentDetails(LsDoc $document): array
    {
        $mirrored = $document->getMirroredFramework();

        return array_merge(
            $this->documentSummary($document),
            [
                'changed_at' => $this->formatDate($document->getChangedAt()),
                'publisher' => $document->getPublisher(),
                'version' => $document->getVersion(),
                'description' => $document->getDescription(),
                'official_uri' => $document->getOfficialUri(),
                'status_start' => $this->formatDate($document->getStatusStart()),
                'status_end' => $this->formatDate($document->getStatusEnd()),
                'url_name' => $document->getUrlName(),
                'is_mirrored' => $document->isMirrored(),
                'mirrored_visible' => $mirrored?->isVisible(),
                'item_count' => $document->getLsItems()->count(),
                'subjects' => $this->subjectTitles($document),
                'subject_codes' => array_values($document->getSubject() ?? []),
            ]
        );
    }

    /**
     * @return array{
     *     id: ?int,
     *     identifier: string,
     *     uri: string,
     *     document_identifier: ?string,
     *     document_uri: ?string,
     *     object_type: string,
     *     item_type: ?string,
     *     human_coding_scheme: ?string,
     *     abbreviated_statement: ?string,
     *     full_statement: ?string,
     *     updated_at: string
     * }
     */
    public function itemSummary(LsItem $item): array
    {
        return [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'uri' => $item->getUri(),
            'document_identifier' => $item->getLsDocIdentifier(),
            'document_uri' => $item->getLsDocUri(),
            'object_type' => $item->getObjectType(),
            'item_type' => $item->getType(),
            'human_coding_scheme' => $item->getHumanCodingScheme(),
            'abbreviated_statement' => $item->getAbbreviatedStatement(),
            'full_statement' => $item->getFullStatement(),
            'updated_at' => $this->formatDate($item->getUpdatedAt()),
        ];
    }

    /**
     * @return array{
     *     id: ?int,
     *     identifier: string,
     *     uri: string,
     *     document_identifier: ?string,
     *     document_uri: ?string,
     *     object_type: string,
     *     item_type: ?string,
     *     human_coding_scheme: ?string,
     *     abbreviated_statement: ?string,
     *     full_statement: ?string,
     *     updated_at: string,
     *     changed_at: string,
     *     list_enum_in_source: ?string,
     *     language: ?string,
     *     concept_keywords: list<string>,
     *     educational_alignment: list<string>,
     *     notes: ?string,
     *     parent_identifiers: list<string>,
     *     child_count: int
     * }
     */
    public function itemDetails(LsItem $item): array
    {
        return array_merge(
            $this->itemSummary($item),
            [
                'changed_at' => $this->formatDate($item->getChangedAt()),
                'list_enum_in_source' => $item->getListEnumInSource(),
                'language' => $item->getLanguage(),
                'concept_keywords' => array_values($item->getConceptKeywordsArray()),
                'educational_alignment' => $this->splitCsv($item->getEducationalAlignment()),
                'notes' => $item->getNotes(),
                'parent_identifiers' => array_values($item->getLsItemParent()->map(static fn (LsItem $parent): string => $parent->getIdentifier())->toArray()),
                'child_count' => $item->getChildren()->count(),
            ]
        );
    }

    /**
     * @return list<string>
     */
    private function subjectTitles(LsDoc $document): array
    {
        $subjects = [];
        foreach ($document->getSubjects() as $subject) {
            $subjects[] = $subject->getTitle();
        }

        return $subjects;
    }

    /**
     * @return list<string>
     */
    private function splitCsv(?string $value): array
    {
        if (null === $value || '' === trim($value)) {
            return [];
        }

        $values = array_map('trim', explode(',', $value));

        return array_values(array_filter($values, static fn (string $entry): bool => '' !== $entry));
    }

    private function formatDate(?\DateTimeInterface $dateTime): ?string
    {
        if (null === $dateTime) {
            return null;
        }

        return $dateTime->format(\DateTimeInterface::ATOM);
    }
}
