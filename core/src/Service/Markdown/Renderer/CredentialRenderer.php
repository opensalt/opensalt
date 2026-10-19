<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\DTO\ItemType\CredentialDto;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\CredentialAssociationsResolver;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\UriGenerator;

final readonly class CredentialRenderer extends DefaultItemRenderer
{
    use PropertiesSectionTrait;

    public function __construct(
        HtmlToMarkdownConverter $htmlConverter,
        private CredentialAssociationsResolver $associationsResolver,
        private UriGenerator $uriGenerator,
    ) {
        parent::__construct($htmlConverter);
    }

    #[\Override]
    public function supports(LsItem $item): bool
    {
        return LsItemKind::Credential === LsItemKind::tryFrom($item->getDiscriminator());
    }

    #[\Override]
    public function render(LsItem $item, array $context = []): string
    {
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);
        $isPackage = (bool) ($context['markdown-package'] ?? false);
        $credential = $this->decodeOb3($item);

        $description = $this->htmlConverter->convert(
            is_string($credential['description'] ?? null) ? $credential['description'] : $item->getFullStatement()
        );

        $blocks = [];
        if ('' !== $description) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(2 + $offset, 'Statement'),
                MarkdownBuilder::p(MarkdownBuilder::demoteHeadings($description, 3 + $offset)),
            ]);
        }

        $narrative = $this->htmlConverter->convert(
            is_string($credential['criteria']['narrative'] ?? null) ? $credential['criteria']['narrative'] : null
        );

        $resolved = $this->associationsResolver->resolve($item);

        $criteriaBlocks = [];
        if ('' !== $narrative) {
            $criteriaBlocks[] = MarkdownBuilder::p(MarkdownBuilder::demoteHeadings($narrative, 3 + $offset));
        }
        if ([] !== $resolved['criteria']) {
            $criteriaBlocks[] = MarkdownBuilder::bullets(array_map(
                fn (LsItem $criterion) => $this->itemLink($criterion),
                array_values($resolved['criteria'])
            ));
        }
        if ([] !== $criteriaBlocks) {
            $blocks[] = MarkdownBuilder::joinBlocks([MarkdownBuilder::h(2 + $offset, 'Criteria (predecessors)'), ...$criteriaBlocks]);
        }

        $alignmentLines = $this->alignmentLines($item, $resolved['alignments'], $credential);
        if ([] !== $alignmentLines) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(2 + $offset, 'Alignments'),
                implode("\n", $alignmentLines),
            ]);
        }

        $blocks[] = $this->notesSection($item, $offset);

        $blocks[] = $this->propertiesSection([
            'Achievement type' => is_string($credential['achievementType'] ?? null) ? $credential['achievementType'] : null,
            'Human code' => is_string($credential['humanCode'] ?? null) && '' !== $credential['humanCode'] ? $credential['humanCode'] : $item->getHumanCodingScheme(),
            'Language' => is_string($credential['inLanguage'] ?? null) ? $credential['inLanguage'] : null,
            'Tags' => isset($credential['tag']) && is_array($credential['tag']) ? implode(', ', array_map('strval', $credential['tag'])) : null,
        ], $offset);

        if (!$isPackage) {
            $blocks[] = MarkdownBuilder::joinBlocks([
                MarkdownBuilder::h(3 + $offset, 'Raw credential data'),
                MarkdownBuilder::code('json', $this->rawOb3($item)),
            ]);
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }

    /**
     * CASE alignments (from the resolver) plus the OB3 `alignment` array,
     * which many imported credentials never reify as CASE associations.
     *
     * @param array<string, LsItem> $alignments
     * @param array<mixed> $credential
     *
     * @return array<int, string>
     */
    private function alignmentLines(LsItem $item, array $alignments, array $credential): array
    {
        $sourceDoc = $item->getLsDoc();
        $lines = [];

        foreach (array_values($alignments) as $alignment) {
            $line = '- '.$this->itemLink($alignment);
            $targetDoc = $alignment->getLsDoc();
            if (null !== $targetDoc && null !== $sourceDoc && $targetDoc->getId() !== $sourceDoc->getId()) {
                $line .= ' — Framework: '.$targetDoc->getTitle();
            }
            $lines[] = $line;
        }

        $ob3Alignments = $credential['alignment'] ?? [];
        if (is_array($ob3Alignments)) {
            foreach ($ob3Alignments as $alignment) {
                if (!is_array($alignment)) {
                    continue;
                }
                $name = is_string($alignment['targetName'] ?? null) ? $alignment['targetName'] : null;
                $url = is_string($alignment['targetUrl'] ?? null) ? $alignment['targetUrl'] : null;
                if (null === $name && null === $url) {
                    continue;
                }
                $details = [];
                foreach (['targetCode', 'targetFramework', 'targetType'] as $key) {
                    if (is_string($alignment[$key] ?? null) && '' !== $alignment[$key]) {
                        $details[] = $alignment[$key];
                    }
                }
                $label = (null !== $url && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')))
                    ? MarkdownBuilder::link($name ?? $url, $url)
                    : ($name ?? $url);
                $lines[] = '- '.$label.([] !== $details ? ' — '.implode(' — ', $details) : '');
            }
        }

        return $lines;
    }

    /**
     * @return array<mixed>
     */
    private function decodeOb3(LsItem $item): array
    {
        $raw = $item->getExtensionProperty(CredentialDto::CREDENTIAL_KEY);
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && '' !== $raw) {
            try {
                $decoded = json5_decode($raw, true);
            } catch (\Throwable) {
                $decoded = null;
            }
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function rawOb3(LsItem $item): string
    {
        $raw = $item->getExtensionProperty(CredentialDto::CREDENTIAL_KEY);
        if (is_array($raw)) {
            $raw = json_encode($raw, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }

        return is_string($raw) ? $raw : '{}';
    }

    private function itemLink(LsItem $target): string
    {
        $title = $target->getAbbreviatedStatement() ?? $target->getFullStatement() ?? $target->getIdentifier();
        $scheme = $target->getHumanCodingScheme();
        if (null !== $scheme && '' !== $scheme && $scheme !== $title) {
            $title .= ' ('.$scheme.')';
        }

        return MarkdownBuilder::link($title, $this->uriGenerator->getUri($target) ?? '');
    }
}
