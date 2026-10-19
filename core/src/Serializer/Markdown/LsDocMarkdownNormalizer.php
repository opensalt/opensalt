<?php

declare(strict_types=1);

namespace App\Serializer\Markdown;

use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use App\Serializer\CaseJson\DateCallbackTrait;
use App\Serializer\CaseJson\LastChangeDateTimeTrait;
use App\Service\Api1RouteMap;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\UriGenerator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer', ['priority' => 64])]
final readonly class LsDocMarkdownNormalizer implements NormalizerInterface
{
    use DateCallbackTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private UriGenerator $uriGenerator,
        private HtmlToMarkdownConverter $htmlConverter,
        private LsDocRepository $docRepository,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LsDoc
            && 'md' === $format
            && empty($context['markdown-package']);
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [LsDoc::class => true];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!$data instanceof LsDoc) {
            return null;
        }

        $tocRows = $this->docRepository->findItemTocRows($data);
        $parentMap = $this->docRepository->findItemParentMap($data);

        $kindCounts = [];
        foreach ($tocRows as $row) {
            $kindCounts[$row['kind']] = ($kindCounts[$row['kind']] ?? 0) + 1;
        }
        ksort($kindCounts);

        $frontMatter = array_filter([
            'objectType' => 'Document',
            'identifier' => $data->getIdentifier(),
            'uri' => $this->uriGenerator->getUri($data),
            'title' => $data->getTitle(),
            'creator' => $data->getCreator(),
            'publisher' => $data->getPublisher(),
            'version' => $data->getVersion(),
            'officialUri' => $data->getOfficialUri(),
            'adoptionStatus' => $data->getAdoptionStatus(),
            'frameworkType' => $data->getFrameworkType()?->getFrameworkType(),
            'subject' => ([] !== ($data->getSubject() ?? [])) ? $data->getSubject() : null,
            'language' => $data->getLanguage(),
            'licenseUri' => $this->licenseUri($data),
            'statusStartDate' => $this->toDate($data->getStatusStart()),
            'statusEndDate' => $this->toDate($data->getStatusEnd()),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
            'itemCount' => count($tocRows),
            'kindCounts' => ([] !== $kindCounts) ? $kindCounts : null,
        ], static fn (mixed $v): bool => null !== $v && [] !== $v);

        $blocks = [
            MarkdownBuilder::h(1, $data->getTitle() ?? $data->getIdentifier() ?? 'Framework'),
            $this->aboutSection($data),
            $this->topLevelItemsSection($tocRows, $parentMap),
        ];

        $packageUri = $this->uriGenerator->getUri($data, Api1RouteMap::getForClass(\App\Entity\Framework\Package::class));

        if (null !== $packageUri) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(2, 'Full package'),
                MarkdownBuilder::p(MarkdownBuilder::link(
                    'Complete framework with all items (markdown)',
                    $packageUri
                )),
            ]);
        }

        return ['front_matter' => $frontMatter, 'body' => MarkdownBuilder::joinBlocks($blocks)];
    }

    /**
     * The top-level items, so an agent can route a query without streaming
     * the whole package.
     *
     * @param array<int, array{identifier: string, title: string, code: ?string, kind: string}> $tocRows
     * @param array<string, ?string> $parentMap
     */
    private function topLevelItemsSection(array $tocRows, array $parentMap): string
    {
        // top-level = no item parent: parent is null, the doc itself, or unknown
        $topRows = array_values(array_filter($tocRows, static function (array $row) use ($parentMap): bool {
            $parent = $parentMap[$row['identifier']] ?? null;

            return null === $parent || !isset($parentMap[$parent]);
        }));

        if ([] === $topRows) {
            return '';
        }

        $lines = [];
        foreach ($topRows as $row) {
            $label = $row['title'];
            if (null !== $row['code'] && '' !== $row['code'] && $row['code'] !== $label) {
                $label .= ' ('.$row['code'].')';
            }
            $uri = $this->uriGenerator->getPublicUriForIdentifier($row['identifier']);
            $lines[] = '- '.(null !== $uri ? MarkdownBuilder::link($label, $uri) : $label).' — '.$row['kind'];
        }

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2, 'Top-level items'),
            implode("\n", $lines),
        ]);
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
}
