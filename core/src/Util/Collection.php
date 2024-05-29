<?php

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
}
