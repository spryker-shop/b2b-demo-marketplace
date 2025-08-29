<?php

namespace Pyz\Client\StorageDatabase\Storage\Reader;

use Propel\Runtime\Connection\StatementInterface;
use Spryker\Client\StorageDatabase\Storage\Reader\MySqlStorageReader as SprykerMySqlStorageReader;

class MySqlStorageReader extends SprykerMySqlStorageReader
{
    /**
     * @var string
     */
    protected const SELECT_STATEMENT_PATTERN = '
      SELECT %1$s as resource_key, data AS resource_data
        FROM %3$s
        WHERE `key` = %1$s
    ';

    /**
     * @param array<string> $resourceKeys
     *
     * @return array
     */
    protected function prepareMultiTableQueryData(array $resourceKeys): array
    {
        $resourceKeys = array_values($resourceKeys);

        return parent::prepareMultiTableQueryData($resourceKeys);
    }

    /**
     * @param array $resourceKeys
     *
     * @return \Propel\Runtime\Connection\StatementInterface
     */
    protected function createMultiSelectStatementForResourceKeys(array $resourceKeys): StatementInterface
    {
        $queryDataPerTable = $this->prepareMultiTableQueryData($resourceKeys);
        [$queryDataPerTable, $statement] = $this->buildMultiTableSelectStatementMulti($queryDataPerTable);

        return $this->bindValuesToStatement($statement, $queryDataPerTable);
    }

    /**
     * @param array $queryDataPerTable
     *
     * @return array
     */
    protected function buildMultiTableSelectStatementMulti(array $queryDataPerTable): array
    {
        $selectFragments = [];
        $queryData = [];

        foreach ($queryDataPerTable as $tableName => $tableQueryData) {
            if (count($tableQueryData)>1) {
                $query = "
      SELECT `key` as resource_key, `data` AS resource_data
        FROM $tableName
        WHERE `key` IN ( ";
                $keys = [];
                foreach ($tableQueryData as $dataSet) {
                    /**
                     * @var string $keyPlaceholder
                     * @var string $aliasKeyPlaceholder
                     */
                    [$keyPlaceholder, $aliasKeyPlaceholder] = array_keys($dataSet);
                    $keys[] = $keyPlaceholder;
                    $queryData[$tableName][][$keyPlaceholder] = $dataSet[$keyPlaceholder];
                }
                $query .= implode(',', $keys) . ' )';
                $selectFragments[] = $query;
                continue;
            }
            foreach ($tableQueryData as $dataSet) {
                /**
                 * @var string $keyPlaceholder
                 * @var string $aliasKeyPlaceholder
                 */
                [$keyPlaceholder, $aliasKeyPlaceholder] = array_keys($dataSet);
                $selectFragments[] = $this->buildSelectQuerySql($tableName, $keyPlaceholder, $aliasKeyPlaceholder);
            }
            $queryData[$tableName] = $tableQueryData;
        }

        $selectSqlString = implode(' UNION ', $selectFragments);

        return [$queryData, $this->createPreparedStatement($selectSqlString)];
    }

}
