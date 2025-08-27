<?php

namespace Pyz\Client\StorageDatabase\Storage\Reader;

use Propel\Runtime\Connection\StatementInterface;
use Spryker\Client\StorageDatabase\Storage\Reader\PostgreSqlStorageReader as SprykerPostgreSqlStorageReader;

class PostgreSqlStorageReader extends SprykerPostgreSqlStorageReader
{
    protected const SELECT_STATEMENT_PATTERN = '
      SELECT %1$s::VARCHAR as resource_key, "data" AS resource_data
      FROM %2$s
      WHERE "key" = %1$s::VARCHAR OR "alias_keys" = %1$s::VARCHAR
    ';

    /**
     * @param array $queryDataPerTable
     *
     * @return \Propel\Runtime\Connection\StatementInterface
     */
    protected function buildMultiTableSelectStatement(array $queryDataPerTable): StatementInterface
    {
        $selectFragments = [];

        /**
         * @var string $tableName
         * @var array<string> $tableQueryData
         */
        foreach ($queryDataPerTable as $tableName => $tableQueryData) {
            if (count($tableQueryData) > 1) {
                $keys = [];
                foreach (array_keys($tableQueryData) as $keyPlaceholder) {
                    $keys[] = $keyPlaceholder;
                }
                $query = '
      SELECT "key" as resource_key, "data" AS resource_data
        FROM ' . $tableName . '
        WHERE "key" IN ( ';
                $query .= implode(', ', $keys) . ' )';
                $selectFragments[] = $query;
                continue;
            }
            foreach (array_keys($tableQueryData) as $keyPlaceholder) {
                $selectFragments[] = $this->buildSelectQuerySql($tableName, $keyPlaceholder);
            }
        }

        $selectSqlString = implode(' UNION ', $selectFragments);

        return $this->createPreparedStatement($selectSqlString);
    }
}
