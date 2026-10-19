<?php

declare(strict_types=1);

namespace App\Service\Markdown;

/**
 * Picks the display title for an item: abbreviated statement, then a
 * single-line truncation of the full statement, then the human coding
 * scheme.  Most standards have no abbreviated statement, and a heading
 * that is only a code carries no meaning.
 */
final class DisplayTitle
{
    public const int MAX_LENGTH = 100;

    public static function from(?string $abbreviated, ?string $fullStatement, ?string $code = null): string
    {
        $title = self::clean($abbreviated);
        if ('' === $title) {
            $title = self::truncate(self::clean($fullStatement));
        }
        if ('' === $title) {
            $title = self::clean($code);
        }

        return $title;
    }

    public static function clean(?string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');
    }

    public static function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::MAX_LENGTH);
        $space = mb_strrpos($cut, ' ');
        if (false !== $space && $space > 40) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t.,;:").'…';
    }
}
