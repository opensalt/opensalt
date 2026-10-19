<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\Entity\Framework\LsItem;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\ItemSectionRenderer;
use App\Service\Markdown\MarkdownBuilder;

readonly class DefaultItemRenderer implements ItemSectionRenderer
{
    public function __construct(
        protected HtmlToMarkdownConverter $htmlConverter,
    ) {
    }

    #[\Override]
    public function supports(LsItem $item): bool
    {
        return true;
    }

    #[\Override]
    public function render(LsItem $item, array $context = []): string
    {
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);

        return MarkdownBuilder::joinBlocks([
            $this->statementSection($item, $offset),
            $this->notesSection($item, $offset),
        ]);
    }

    protected function statementSection(LsItem $item, int $offset = 0): string
    {
        $statement = $this->htmlConverter->convert($item->getFullStatement());
        if ('' === $statement) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2 + $offset, 'Statement'),
            MarkdownBuilder::p(MarkdownBuilder::demoteHeadings($statement, 3 + $offset)),
        ]);
    }

    protected function notesSection(LsItem $item, int $offset = 0): string
    {
        $notes = $this->htmlConverter->convert($item->getNotes());
        if ('' === $notes) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2 + $offset, 'Notes'),
            MarkdownBuilder::p(MarkdownBuilder::demoteHeadings($notes, 3 + $offset)),
        ]);
    }
}
