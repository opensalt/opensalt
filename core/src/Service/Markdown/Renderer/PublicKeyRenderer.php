<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\DTO\ItemType\PublicKeyDto;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\MarkdownBuilder;

final readonly class PublicKeyRenderer extends DefaultItemRenderer
{
    use PropertiesSectionTrait;

    #[\Override]
    public function supports(LsItem $item): bool
    {
        return LsItemKind::PublicKey === LsItemKind::tryFrom($item->getDiscriminator());
    }

    #[\Override]
    public function render(LsItem $item, array $context = []): string
    {
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);

        $dto = PublicKeyDto::fromItem($item);

        $blocks = [
            $this->propertiesSection([
                'Key ID (kid)' => $dto->kid,
                'Key format' => $dto->type,
            ], $offset),
        ];

        if (null !== $dto->publicKey && '' !== $dto->publicKey) {
            $blocks[] = MarkdownBuilder::code('json', $dto->publicKey);
        }

        return MarkdownBuilder::joinBlocks($blocks);
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
