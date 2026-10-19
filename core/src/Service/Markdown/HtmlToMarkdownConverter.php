<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use League\HTMLToMarkdown\HtmlConverter;

final class HtmlToMarkdownConverter
{
    private HtmlConverter $converter;

    public function __construct()
    {
        $this->converter = new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style',
        ]);
    }

    public function convert(?string $html): string
    {
        if (null === $html || '' === $html) {
            return '';
        }

        // Content stored without markup is already markdown -- running it
        // through the HTML converter would flatten its line structure and
        // escape its markdown syntax.
        if (!preg_match('/<[^>]+>/', $html)) {
            return trim($html);
        }

        try {
            return trim($this->converter->convert($html));
        } catch (\Throwable) {
            return trim((string) strip_tags($html));
        }
    }
}
