<?php

declare(strict_types=1);

namespace App\Serializer\Markdown;

use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use App\Serializer\CaseJson\DateCallbackTrait;
use App\Serializer\CaseJson\LastChangeDateTimeTrait;
use App\Service\Api1RouteMap;
use App\Service\Markdown\AssociationListRenderer;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\UriGenerator;
use Doctrine\ORM\Query;

final readonly class PackageMarkdownNormalizer
{
    use DateCallbackTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private UriGenerator $uriGenerator,
        private AssociationRowsCollector $associationRowsCollector,
        private AssociationListRenderer $associationListRenderer,
        private LsDocRepository $docRepository,
        private HtmlToMarkdownConverter $htmlConverter,
    ) {
    }

    /**
     * @param array<int, string> $tocLines rendered TOC lines (already indented)
     * @param array<string, int> $kindCounts
     * @param array<string, int> $itemTypeCounts
     * @param array<int, array{title: ?string, identifier: ?string, types: array<string, int>}> $crosswalks
     *
     * @return array{front_matter: array<string, mixed>, body: string}
     */
    public function header(
        LsDoc $doc,
        int $itemCount,
        array $tocLines,
        array $kindCounts = [],
        array $itemTypeCounts = [],
        array $crosswalks = [],
    ): array {
        $frontMatter = array_filter([
            'objectType' => 'Package',
            'identifier' => $doc->getIdentifier(),
            'uri' => $this->uriGenerator->getUri($doc, Api1RouteMap::getForClass(\App\Entity\Framework\Package::class)),
            'title' => $doc->getTitle(),
            'creator' => $doc->getCreator(),
            'publisher' => $doc->getPublisher(),
            'version' => $doc->getVersion(),
            'officialUri' => $doc->getOfficialUri(),
            'adoptionStatus' => $doc->getAdoptionStatus(),
            'frameworkType' => $doc->getFrameworkType()?->getFrameworkType(),
            'subject' => ([] !== ($doc->getSubject() ?? [])) ? $doc->getSubject() : null,
            'language' => $doc->getLanguage(),
            'licenseUri' => $this->licenseUri($doc),
            'statusStartDate' => $this->toDate($doc->getStatusStart()),
            'statusEndDate' => $this->toDate($doc->getStatusEnd()),
            'lastChangeDateTime' => $this->getLastChangeDateTime($doc),
            'itemCount' => $itemCount,
            'kindCounts' => ([] !== $kindCounts) ? $kindCounts : null,
            'itemTypeCounts' => ([] !== $itemTypeCounts) ? $itemTypeCounts : null,
        ], static fn (mixed $v): bool => null !== $v && [] !== $v);

        $blocks = [
            MarkdownBuilder::h(1, $doc->getTitle() ?? $doc->getIdentifier() ?? 'Framework'),
            $this->aboutSection($doc),
        ];

        $crosswalkBlock = $this->crosswalksSection($crosswalks);
        if ('' !== $crosswalkBlock) {
            $blocks[] = $crosswalkBlock;
        }

        if ([] !== $tocLines) {
            $blocks[] = MarkdownBuilder::joinBlocks([MarkdownBuilder::h(2, 'Contents'), implode("\n", $tocLines)]);
        }

        if ($itemCount > 0) {
            $blocks[] = MarkdownBuilder::h(2, 'Items');
        }

        return ['front_matter' => $frontMatter, 'body' => MarkdownBuilder::joinBlocks($blocks)];
    }

    public function tailSections(LsDoc $doc): string
    {
        $blocks = [];

        $docRows = $this->associationRowsCollector->collect($doc);
        $assocRows = [...$docRows['outgoing'], ...$docRows['incoming']];
        $rendered = $this->associationListRenderer->render($assocRows);
        if ('' !== $rendered) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(2, 'Document-level associations'),
                $rendered,
            ]);
        }

        $blocks[] = $this->definitionsSection($doc);

        return MarkdownBuilder::joinBlocks($blocks);
    }

    /**
     * Other frameworks this package links to, with association type counts.
     *
     * @param array<int, array{title: ?string, identifier: ?string, types: array<string, int>}> $crosswalks
     */
    private function crosswalksSection(array $crosswalks): string
    {
        if ([] === $crosswalks) {
            return '';
        }

        $blocks = [MarkdownBuilder::h(2, 'Crosswalks')];
        foreach ($crosswalks as $framework) {
            $label = $framework['title'] ?? $framework['identifier'] ?? 'Unknown framework';
            $uri = (null !== $framework['identifier']) ? $this->uriGenerator->getPublicUriForIdentifier($framework['identifier']) : null;
            $heading = null !== $uri ? MarkdownBuilder::link($label, $uri) : $label;
            $blocks[] = MarkdownBuilder::h(3, $heading);

            $types = $framework['types'];
            ksort($types);
            $blocks[] = MarkdownBuilder::bullets(array_map(
                static fn (string $type, int $count) => "{$type}: {$count}",
                array_keys($types),
                array_values($types)
            ));
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }

    private function licenseUri(LsDoc $doc): ?string
    {
        $licence = $doc->getLicence();
        if (null === $licence) {
            return null;
        }

        return $this->uriGenerator->getUri($licence);
    }

    private function aboutSection(LsDoc $doc): string
    {
        $description = $this->htmlConverter->convert($doc->getDescription());

        $lines = array_filter([
            $description,
            null !== $doc->getAdoptionStatus() ? 'Status: '.$doc->getAdoptionStatus() : null,
            ([] !== ($doc->getSubject() ?? [])) ? 'Subjects: '.implode(', ', array_map('strval', $doc->getSubject())) : null,
            null !== $doc->getVersion() ? 'Version: '.$doc->getVersion() : null,
            null !== $doc->getNote() ? 'Note: '.strip_tags($doc->getNote()) : null,
        ], static fn (?string $l) => null !== $l && '' !== $l);

        if ([] === $lines) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([MarkdownBuilder::h(2, 'About this framework'), implode("\n\n", $lines)]);
    }

    private function definitionsSection(LsDoc $doc): string
    {
        $blocks = [MarkdownBuilder::h(2, 'Definitions')];

        $sections = [
            'CFSubjects' => [
                'rows' => array_map(
                    static fn ($s) => [$s->getTitle() ?? '', $s->getIdentifier() ?? '', $s->getHierarchyCode(), $s->getDescription() ?? ''],
                    $doc->getSubjects()->toArray()
                ),
                'hierarchy' => true,
            ],
            'CFItemTypes' => [
                'rows' => array_map(
                    static fn ($t) => [$t->getTitle() ?? '', $t->getIdentifier() ?? '', $t->getHierarchyCode(), $t->getDescription() ?? ''],
                    $this->docRepository->findAllUsedItemTypes($doc, Query::HYDRATE_OBJECT)
                ),
                'hierarchy' => true,
            ],
            'CFLicenses' => [
                'rows' => array_map(
                    static fn ($l) => [$l->getTitle() ?? '', $l->getIdentifier() ?? '', '', $l->getDescription() ?? ''],
                    $this->docRepository->findAllUsedLicences($doc, Query::HYDRATE_OBJECT)
                ),
                'hierarchy' => false,
            ],
            'CFAssociationGroupings' => [
                'rows' => array_map(
                    static fn ($g) => [$g->getTitle() ?? '', $g->getIdentifier() ?? '', '', $g->getDescription() ?? ''],
                    $this->docRepository->findAllUsedAssociationGroups($doc, Query::HYDRATE_OBJECT)
                ),
                'hierarchy' => false,
            ],
            'CFConcepts' => [
                'rows' => array_map(
                    static fn ($c) => [$c->getTitle() ?? '', $c->getIdentifier() ?? '', $c->getHierarchyCode(), $c->getKeywords() ?? ''],
                    $this->docRepository->findAllUsedConcepts($doc, Query::HYDRATE_OBJECT)
                ),
                'hierarchy' => true,
            ],
        ];

        foreach ($sections as $heading => $config) {
            $rows = array_filter($config['rows'], static fn (array $r) => '' !== implode('', $r));
            if ([] === $rows) {
                continue;
            }
            $headers = $config['hierarchy']
                ? ['Title', 'Identifier', 'Hierarchy code', 'Description']
                : ['Title', 'Identifier', 'Description'];
            $tableRows = array_map(
                static fn (array $r) => $config['hierarchy'] ? $r : [$r[0], $r[1], $r[3]],
                $rows
            );
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(3, $heading),
                MarkdownBuilder::table($headers, array_values($tableRows)),
            ]);
        }

        /** @var iterable<\App\Entity\Framework\CfRubric> $rubrics */
        $rubrics = $this->docRepository->findAllUsedRubrics($doc, Query::HYDRATE_OBJECT);
        $rubricRows = [];
        foreach ($rubrics as $rubric) {
            $criteria = [];
            foreach ($rubric->getCriteria() as $criterion) {
                $levels = implode(', ', array_map(
                    static fn ($level) => (string) ($level->getDescription() ?? $level->getQuality() ?? ''),
                    $criterion->getLevels()->toArray()
                ));
                $criteria[] = ($criterion->getDescription() ?? '').('' !== $levels ? ' ('.$levels.')' : '');
            }
            $rubricRows[] = [
                $rubric->getTitle() ?? '',
                implode('; ', array_filter($criteria)),
            ];
        }
        if ([] !== $rubricRows) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(3, 'CFRubrics'),
                MarkdownBuilder::table(['Rubric', 'Criteria (levels)'], $rubricRows),
            ]);
        }

        // The Definitions header is only meaningful if something followed it
        if (1 === count($blocks)) {
            return '';
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }
}
