<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Util;

class CsvUtil
{
    /**
     * Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
     * Adapted from http://php.net/manual/en/function.fputcsv.php#87120
     *
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     *
     * @return string
     */
    public static function arrayToCsv(array &$fields, $delimiter = ',', $enclosure = '"', $encloseAll = true, $nullToMysqlNull = false): string
    {
        $delimiterEsc = preg_quote($delimiter, '/');
        $enclosureEsc = preg_quote($enclosure, '/');

        $output = [];
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match("/(?:${delimiterEsc}|${enclosureEsc}|\s)/", $field)) {
                $output[] = $enclosure.str_replace($enclosure, $enclosure.$enclosure, $field).$enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output);
    }
}
