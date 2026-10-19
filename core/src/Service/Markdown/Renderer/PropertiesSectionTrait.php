<?php

declare(strict_types=1);

namespace App\Service\Markdown\Renderer;

use App\Service\Markdown\MarkdownBuilder;

trait PropertiesSectionTrait
{
    /**
     * @param array<string, ?string> $properties ordered map label => value (null/'' skipped)
     */
    private function propertiesSection(array $properties, int $offset = 0): string
    {
        $lines = [];
        foreach ($properties as $label => $value) {
            if (null === $value || '' === $value) {
                continue;
            }
            $lines[] = '- '.$label.': '.$value;
        }
        if ([] === $lines) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2 + $offset, 'Properties'),
            implode("\n", $lines),
        ]);
    }
}
