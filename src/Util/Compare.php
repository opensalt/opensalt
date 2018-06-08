<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Util;

class Compare
{
    /**
     * Sort an array using multiple fields
     *
     * @param array $itemArray
     * @param array $fields
     */
    public static function sortArrayByFields(array &$itemArray, array $fields): void
    {
        uasort($itemArray, function ($a, $b) use ($fields) {
            foreach ($fields as $field) {
                if (0 !== ($ret = Compare::arrayCompare($a, $b, $field))) {
                    return $ret;
                }
            }

            return 0;
        });
    }
    /**
     * Compare two array fields
     *
     * @param mixed $a
     * @param mixed $b
     * @param string $key
     * @param bool $setValueIsLower
     *
     * @return int
     */
    public static function arrayCompare($a, $b, $key, $setValueIsLower = true): int
    {
        if (!isset($a[$key]) && !isset($b[$key])) {
            return 0;
        }

        if (0 !== ($ret = self::isSetInArray($a, $b, $key, $setValueIsLower))) {
            return $ret;
        }

        $x = $a[$key];
        $y = $b[$key];

        if ((string) $x === (string) $y) {
            return 0;
        }

        if (is_numeric($x) && is_numeric($y)) {
            return ((float) $x < (float) $y) ? -1 : 1;
        }

        $xa = preg_split('/[\s.,-]/', $x);
        $ya = preg_split('/[\s.,-]/', $y);
        $len = \count($xa);
        if ($len < \count($ya)) {
            $len = \count($ya);
        }
        if (1 < $len) {
            for ($idx = 0; $idx < $len; ++$idx) {
                if (0 !== ($ret = self::arrayCompare($xa, $ya, $idx, false))) {
                    return $ret;
                }
            }
        }

        return strcmp($x, $y);
    }

    /**
     * Returns < 0 if $a[$key] is set and $b[$key] is not,
     * > 0 if $b[$key] is set and $a[$key] is not,
     * 0 if both are set or unset
     *
     * @param array $a
     * @param array $b
     * @param string $key
     * @param bool $setValueIsLower
     *
     * @return int
     */
    public static function isSetInArray($a, $b, $key, $setValueIsLower = true): int
    {
        $dir = ($setValueIsLower ? 1 : -1);

        if (isset($a[$key])) {
            if (isset($b[$key])) {
                return 0;
            }

            return -1*$dir;
        }

        if (isset($b[$key])) {
            return 1*$dir;
        }

        return 0;
    }
}
