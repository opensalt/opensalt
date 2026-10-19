<?php

declare(strict_types=1);

namespace App\Serializer\Markdown;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Serializer\CaseJson\DateCallbackTrait;
use App\Serializer\CaseJson\LastChangeDateTimeTrait;
use App\Service\Api1Uris;
use App\Service\Markdown\AssociationListRenderer;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\Markdown\DisplayTitle;
use App\Service\Markdown\ItemRendererLocator;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\UriGenerator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer', ['priority' => 64])]
final readonly class LsItemMarkdownNormalizer implements NormalizerInterface
{
    use DateCallbackTrait;
    use LastChangeDateTimeTrait;

    /** Extension keys lifted into named front-matter/body fields by the kind renderers. */
    private const array LIFTED_EXTENSIONS = [
        'ob3',
        LsItem::TYPE_KEY,
    ];

    public function __construct(
        private Api1Uris $api1Uris,
        private UriGenerator $uriGenerator,
        private AssociationRowsCollector $associationRowsCollector,
        private AssociationListRenderer $associationListRenderer,
        private ItemRendererLocator $itemRendererLocator,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LsItem && 'md' === $format;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [LsItem::class => true];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!$data instanceof LsItem) {
            return null;
        }

        $isPackage = (bool) ($context['markdown-package'] ?? false);
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);
        $extensions = $data->getExtensions() ?? [];

        $frontMatter = array_filter([
            'objectType' => 'Item',
            'kind' => $this->kindOf($data),
            'identifier' => $data->getIdentifier(),
            'uri' => $this->uriGenerator->getUri($data),
            'frameworkIdentifier' => $data->getLsDoc()?->getIdentifier(),
            'frameworkUri' => $this->uriGenerator->getUri($data->getLsDoc()),
            'frameworkTitle' => $data->getLsDoc()?->getTitle(),
            'title' => $this->titleOf($data),
            'itemType' => $data->getItemType()?->getTitle(),
            'humanCodingScheme' => $data->getHumanCodingScheme(),
            'listEnumeration' => $data->getListEnumInSource(),
            'alternativeLabel' => $data->getAlternativeLabel(),
            'conceptKeywords' => [] !== $data->getConceptKeywordsArray() ? $data->getConceptKeywordsArray() : null,
            'subject' => ([] !== ($data->getSubject() ?? [])) ? $data->getSubject() : null,
            'language' => $data->getLanguage(),
            'educationLevel' => $this->api1Uris->splitByComma($data->getEducationalAlignment()),
            'licenseUri' => $this->licenseUri($data),
            'statusStartDate' => $this->toDate($data->getStatusStart()),
            'statusEndDate' => $this->toDate($data->getStatusEnd()),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
            'extensions' => $this->simpleExtensions($extensions),
        ], static fn (mixed $v): bool => null !== $v && [] !== $v);

        $body = MarkdownBuilder::joinBlocks([
            $this->heading($data, $context),
            $isPackage ? $this->identityLine($data) : '',
            $isPackage ? $this->metadataLine($data, $context) : '',
            $isPackage ? $this->breadcrumbLine($context) : '',
            $isPackage ? '' : $this->ancestorPathSection($data),
            $this->itemRendererLocator->for($data)->render($data, $context),
            $this->rawExtensionsSection($extensions, $isPackage),
            $this->associationsSection($data, $offset),
        ]);

        return ['front_matter' => $frontMatter, 'body' => $body];
    }

    private function kindOf(LsItem $item): string
    {
        return LsItemKind::tryFrom($item->getDiscriminator())?->objectType() ?? 'item';
    }

    private function titleOf(LsItem $item): string
    {
        $title = DisplayTitle::from($item->getAbbreviatedStatement(), $item->getFullStatement(), $item->getHumanCodingScheme());
        if ('' !== $title) {
            return $title;
        }

        return trim(($item->getItemType()?->getTitle() ?? 'CFItem').' '.$item->getIdentifier());
    }

    private function licenseUri(LsItem $item): ?string
    {
        $licence = $item->getLicence();
        if (null === $licence) {
            return null;
        }

        return $this->uriGenerator->getUri($licence);
    }

    /**
     * Simple extensions (scalars and flat arrays of scalars) go to front
     * matter, minus keys the kind renderers already lift into named fields;
     * complex nested values are rendered in the body (see rawExtensionsSection).
     *
     * @return array<string, mixed>|null
     */
    private function simpleExtensions(array $extensions): ?array
    {
        $simple = [];
        foreach ($extensions as $key => $value) {
            if (null === $value || in_array($key, self::LIFTED_EXTENSIONS, true)) {
                continue;
            }
            if (is_scalar($value)) {
                $simple[$key] = $value;
                continue;
            }
            if (is_array($value) && [] === $value) {
                continue;
            }
            if (is_array($value) && array_is_list($value) && [] === array_filter($value, static fn ($v) => !is_scalar($v))) {
                $simple[$key] = $value;
            }
        }

        return [] === $simple ? null : $simple;
    }

    private function rawExtensionsSection(array $extensions, bool $isPackage): string
    {
        if ($isPackage) {
            return '';
        }

        $complex = array_filter($extensions, static fn ($v) => null !== $v && !is_scalar($v) && !([] === $v));
        if ([] === $complex) {
            return '';
        }

        $blocks = [MarkdownBuilder::h(2, 'Extensions')];
        foreach ($complex as $key => $value) {
            $blocks[] = MarkdownBuilder::code(
                'json',
                (string) json_encode([$key => $value], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }

    private function heading(LsItem $item, array $context): string
    {
        if (null !== ($context['markdown-heading'] ?? null)) {
            return MarkdownBuilder::h((int) $context['markdown-heading']['level'], (string) $context['markdown-heading']['text']);
        }

        return MarkdownBuilder::h(1, $this->headingText($item));
    }

    private function headingText(LsItem $item): string
    {
        $title = $this->titleOf($item);
        $scheme = $item->getHumanCodingScheme();
        if (null !== $scheme && '' !== $scheme && $scheme !== $title) {
            return $title.' ('.$scheme.')';
        }

        return $title;
    }

    /**
     * The path from the framework down to the item itself, as a numbered
     * list -- gives isolated item fetches their place in the hierarchy.
     * Package fetches omit it (the Contents numbering carries position).
     */
    private function ancestorPathSection(LsItem $item): string
    {
        $ancestors = [];
        $seen = [spl_object_id($item) => true];
        for ($ancestor = $item->getParentItem(); $ancestor instanceof LsItem && !isset($seen[spl_object_id($ancestor)]); $ancestor = $ancestor->getParentItem()) {
            $seen[spl_object_id($ancestor)] = true;
            $ancestors[] = $ancestor;
        }
        $ancestors = array_reverse($ancestors);

        $doc = $item->getLsDoc();
        if (null === $doc && [] === $ancestors) {
            return '';
        }

        $lines = [];
        $n = 1;
        if (null !== $doc) {
            $label = $doc->getTitle() ?? $doc->getIdentifier() ?? 'Framework';
            $uri = $this->uriGenerator->getUri($doc);
            $lines[] = $n++.'. '.(null !== $uri ? MarkdownBuilder::link($label, $uri) : $label);
        }
        foreach ($ancestors as $ancestor) {
            $label = $this->labelWithCode($ancestor);
            $uri = $this->uriGenerator->getUri($ancestor);
            $lines[] = $n++.'. '.(null !== $uri ? MarkdownBuilder::link($label, $uri) : $label);
        }
        $lines[] = $n++.'. '.$this->labelWithCode($item);

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2, 'Ancestor Path'),
            implode("\n", $lines),
        ]);
    }

    private function labelWithCode(LsItem $item): string
    {
        $title = DisplayTitle::from($item->getAbbreviatedStatement(), $item->getFullStatement(), $item->getHumanCodingScheme());
        if ('' === $title) {
            $title = $item->getIdentifier() ?? 'Item';
        }

        $scheme = $item->getHumanCodingScheme();
        if (null !== $scheme && '' !== $scheme && $scheme !== $title) {
            $title .= ' ('.$scheme.')';
        }

        return $title;
    }

    private function identityLine(LsItem $item): string
    {
        $parts = [
            MarkdownBuilder::inlineCode('Item'),
            MarkdownBuilder::inlineCode($this->kindOf($item)),
            'identifier: '.MarkdownBuilder::inlineCode($item->getIdentifier() ?? ''),
        ];
        $uri = $this->uriGenerator->getUri($item);
        if (null !== $uri) {
            $parts[] = MarkdownBuilder::link('source', $uri);
        }

        return implode(' · ', $parts);
    }

    /**
     * Chunk-surviving metadata: the fields an embedded fragment needs once it
     * has been cut away from the front matter.
     */
    private function metadataLine(LsItem $item, array $context): string
    {
        $parts = [];
        $add = static function (string $label, mixed $value) use (&$parts): void {
            if (null === $value || '' === $value || [] === $value) {
                return;
            }
            $parts[] = $label.': '.(is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value);
        };

        $add('itemType', $item->getItemType()?->getTitle());
        $add('humanCodingScheme', $item->getHumanCodingScheme());
        $add('listEnumeration', $item->getListEnumInSource());
        $add('language', $item->getLanguage());
        $add('educationLevel', $this->api1Uris->splitByComma($item->getEducationalAlignment()));
        $add('conceptKeywords', [] !== $item->getConceptKeywordsArray() ? $item->getConceptKeywordsArray() : null);

        if ([] === $parts) {
            return '';
        }

        return implode(' · ', $parts);
    }

    private function breadcrumbLine(array $context): string
    {
        $breadcrumb = $context['markdown-breadcrumb'] ?? null;
        if (!is_string($breadcrumb) || '' === $breadcrumb) {
            return '';
        }

        return 'Path: '.$breadcrumb;
    }

    private function associationsSection(LsItem $item, int $offset): string
    {
        $rows = $this->associationRowsCollector->collect($item);
        $all = [...$rows['outgoing'], ...$rows['incoming']];
        if ([] === $all) {
            return '';
        }

        $rendered = $this->associationListRenderer->render($all, $offset);
        if ('' === $rendered) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2 + $offset, 'Associations'),
            $rendered,
        ]);
    }
}
