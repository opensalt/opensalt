<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\DTO\ItemType\CourseDto;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\MarkdownBuilder;

final readonly class CourseRenderer extends DefaultItemRenderer
{
    use PropertiesSectionTrait;

    #[\Override]
    public function supports(LsItem $item): bool
    {
        return LsItemKind::Course === LsItemKind::tryFrom($item->getDiscriminator());
    }

    #[\Override]
    public function render(LsItem $item, array $context = []): string
    {
        $offset = (int) ($context['markdown-heading-offset'] ?? 0);

        $dto = CourseDto::fromItem($item);

        return MarkdownBuilder::joinBlocks([
            $this->statementSection($item, $offset),
            $this->notesSection($item, $offset),
            $this->propertiesSection([
                'Course code' => $dto->codedNotation,
                'Delivery type' => $dto->deliveryType,
                'Webpage' => $this->linkIfUrl($dto->webpage),
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
