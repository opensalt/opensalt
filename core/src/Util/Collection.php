<?php

declare(strict_types=1);

namespace App\Util;

class Collection
{
    public static function removeEmptyElements(mixed $arr, array $values = [null, []]): mixed
    {
        if (!is_array($arr)) {
            return $arr;
        }

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = self::removeEmptyElements($value, $values);
            }

            if (in_array($arr[$key], $values, true)) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Convert an empty string to null, leaving all other values (including null) unchanged.
     *
     * Used in CASE export normalizers to treat nullable fields that contain an empty
     * string as if they were null, so removeEmptyElements() strips them from the output.
     * Non-nullable fields with empty strings are not affected since they are not wrapped
     * with this helper.
     */
    public static function emptyToNull(?string $value): ?string
    {
        return '' === $value ? null : $value;
    }
}
