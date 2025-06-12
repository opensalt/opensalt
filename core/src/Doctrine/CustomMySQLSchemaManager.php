<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Schema\MySQLSchemaManager;

/**
 * Ignore custom indexes: https://medium.com/yousign-engineering-product/ignore-custom-indexes-on-doctrine-dbal-b5131dd22071.
 *
 * Ignore functional indexes (null columns) https://github.com/doctrine/dbal/pull/6811/files.
 */
class CustomMySQLSchemaManager extends MySQLSchemaManager
{
    private const array INDEXES_TO_FILTER = [
        'item_atId',
    ];

    #[\Override]
    protected function _getPortableTableIndexesList(array $tableIndexes, string $tableName): array
    {
        foreach ($tableIndexes as $k => $v) {
            $v = array_change_key_case($v, CASE_LOWER);
            if (in_array($v['key_name'], self::INDEXES_TO_FILTER, true)) {
                // Ignore specific set of indexes
                unset($tableIndexes[$k]);
                continue;
            }

            if (null === $v['column_name']) {
                // Ignore indexes with no columns (functional indexes)
                unset($tableIndexes[$k]);
                continue;
            }

            $v['primary'] = 'PRIMARY' === $v['key_name'];

            if (str_contains($v['index_type'], 'FULLTEXT')) {
                $v['flags'] = ['FULLTEXT'];
            } elseif (str_contains($v['index_type'], 'SPATIAL')) {
                $v['flags'] = ['SPATIAL'];
            }

            // Ignore prohibited prefix `length` for spatial index
            if (!str_contains($v['index_type'], 'SPATIAL')) {
                $v['length'] = isset($v['sub_part']) ? (int) $v['sub_part'] : null;
            }

            $tableIndexes[$k] = $v;
        }

        return parent::_getPortableTableIndexesList($tableIndexes, $tableName);
    }
}
