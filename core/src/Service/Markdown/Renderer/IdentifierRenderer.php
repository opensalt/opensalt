<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\DTO\ItemType\IdentifierDto;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\MarkdownBuilder;

final readonly class IdentifierRenderer extends DefaultItemRenderer
{
    use PropertiesSectionTrait;

    #[\Override]
    public function supports(LsItem $item): bool
    {
        return LsItemKind::Identifier === LsItemKind::tryFrom($item->getDiscriminator());
    }

    #[\Override]
    public function render(LsItem $item, array $context = []): string
    {
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);

        $dto = IdentifierDto::fromItem($item);

        return MarkdownBuilder::joinBlocks([
            $this->statementSection($item, $offset),
            $this->notesSection($item, $offset),
            $this->propertiesSection([
                'Identifier' => null !== $dto->identifier ? MarkdownBuilder::inlineCode($dto->identifier) : null,
                'Identifier type' => $dto->type,
            ], $offset),
        ]);
    }

    private function linkIfUrl(?string $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return MarkdownBuilder::link($value, $value);
        }

        return $value;
    }
}
