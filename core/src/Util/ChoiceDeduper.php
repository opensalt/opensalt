<?php

declare(strict_types=1);

namespace App\Util;

use App\Entity\Framework\CaseApiInterface;

/**
 * Builds choice lists that hide duplicate rows from lookup tables which
 * contain the same title more than once, without changing the stored data.
 */
final class ChoiceDeduper
{
    /**
     * Keep one row per label, ignoring case and all whitespace differences
     * (leading, trailing and repeated). Rows referenced by $preferred win
     * over the first-seen row so values already saved on the form data stay
     * valid.
     *
     * The result is sorted by label (case-insensitively).
     *
     * @template T of CaseApiInterface
     *
     * @param list<T> $rows
     * @param iterable<T|null> $preferred
     * @param callable(T): ?string $getLabel
     *
     * @return list<T>
     */
    public static function onePerLabel(array $rows, iterable $preferred, callable $getLabel): array
    {
        $preferredIds = [];
        foreach ($preferred as $row) {
            if (null !== $row) {
                $preferredIds[$row->getId()] = true;
            }
        }

        $byLabel = [];
        foreach ($rows as $row) {
            $key = self::labelKey((string) $getLabel($row));
            if (!isset($byLabel[$key]) || isset($preferredIds[$row->getId()])) {
                $byLabel[$key] = $row;
            }
        }

        usort($byLabel, static fn (CaseApiInterface $a, CaseApiInterface $b): int => strcasecmp(
            (string) $getLabel($a),
            (string) $getLabel($b),
        ));

        return $byLabel;
    }

    private static function labelKey(string $label): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($label)) ?? $label);
    }
}
