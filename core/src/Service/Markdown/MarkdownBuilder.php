<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use Symfony\Component\Yaml\Yaml;

final class MarkdownBuilder
{
    public static function frontMatter(array $fm): string
    {
        if ([] === $fm) {
            return '';
        }

        return "---\n".self::yaml($fm)."---\n";
    }

    public static function yaml(array $fm): string
    {
        return Yaml::dump(
            $fm,
            6,
            2,
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        );
    }

    public static function h(int $level, string $text): string
    {
        return str_repeat('#', max(1, min(6, $level))).' '.$text;
    }

    /**
     * Shift every heading in converted statement content down so the smallest
     * one lands at $minLevel -- keeps embedded headings from punching above
     * the section that contains them.
     */
    public static function demoteHeadings(string $markdown, int $minLevel): string
    {
        $levels = [];
        if (preg_match_all('/^(#{1,6})\s/m', $markdown, $matches)) {
            foreach ($matches[1] as $hashes) {
                $levels[] = strlen($hashes);
            }
        }
        if ([] === $levels) {
            return $markdown;
        }

        $shift = max(0, $minLevel - min($levels));
        if (0 === $shift) {
            return $markdown;
        }

        return preg_replace_callback(
            '/^(#{1,6})\s/m',
            static fn (array $m) => str_repeat('#', min(6, strlen($m[1]) + $shift)).' ',
            $markdown
        );
    }

    public static function p(string $text): string
    {
        return $text;
    }

    public static function table(array $headers, array $rows): string
    {
        $lines = [
            '| '.implode(' | ', array_map(static fn ($h) => self::escapeCell((string) $h), $headers)).' |',
            '|'.implode('|', array_fill(0, count($headers), '---')).'|',
        ];
        foreach ($rows as $row) {
            $lines[] = '| '.implode(' | ', array_map(static fn ($c) => self::escapeCell((string) $c), array_values($row))).' |';
        }

        return implode("\n", $lines);
    }

    public static function bullets(array $items): string
    {
        return implode("\n", array_map(static fn ($i) => '- '.$i, $items));
    }

    public static function code(string $lang, string $text): string
    {
        return '```'.$lang."\n".$text."\n```";
    }

    public static function link(string $text, string $href): string
    {
        return '['.str_replace(['[', ']'], ['\\[', '\\]'], $text).']('.$href.')';
    }

    public static function inlineCode(string $text): string
    {
        return '`'.str_replace('`', '\'', $text).'`';
    }

    public static function escapeCell(string $text): string
    {
        return str_replace(['|', "\r\n", "\n", "\r"], ['\\|', '<br>', '<br>', '<br>'], $text);
    }

    public static function joinBlocks(array $blocks): string
    {
        return implode("\n\n", array_filter(array_map('trim', $blocks), static fn (string $b) => '' !== $b));
    }
}
