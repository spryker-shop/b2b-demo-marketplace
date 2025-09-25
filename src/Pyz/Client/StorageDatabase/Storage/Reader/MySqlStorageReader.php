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
      SELECT %1$s as resource_key, data AS resource_data, `key` AS key_column, `alias_keys` AS alias_keys_column
        FROM %3$s
        WHERE `key` = %1$s OR `alias_keys` = %1$s
    ';

    /**
     * @param string $resourceKey
     *
     * @return string
     */
    public function get(string $resourceKey): string
    {
        $statement = $this->createSingleSelectStatementForResourceKey($resourceKey);
        $statement->execute();

        return $this->fetchSingleResult($statement, $resourceKey);
    }

    /**
     * @param \Propel\Runtime\Connection\StatementInterface $statement
     *
     * @return string
     */
    protected function fetchSingleResult(StatementInterface $statement, string $resourceKey = ''): string
    {
        $result = $statement->fetch();

        if (isset($result['alias_keys_column']) && $result['alias_keys_column'] === $resourceKey) {
            $explode = explode(':', $result['key_column']);
            $id = end($explode);
            return json_encode(['id' => (int)$id]);
        }

        return $result['resource_data'] ?? '';
    }

    /**
     * @param \Propel\Runtime\Connection\StatementInterface $statement
     * @param array<string> $resourceKeys
     *
     * @return array
     */
    protected function fetchMultiResults(StatementInterface $statement, array $resourceKeys): array
    {
        $results = $statement->fetchAll();
        $formattedResults = [];

        if (!$results) {
            return $formattedResults;
        }

        foreach ($results as $result) {
            $resourceKey = $result['resource_key'] ?? null;
            $resourceData = $result['resource_data'] ?? null;

            if (!$resourceKey || !$resourceData) {
                continue;
            }

            if (isset($result['alias_keys_column']) && $result['alias_keys_column'] === $result['resource_key']) {
                $explode = explode(':', trim($result['key_column']));
                $id = end($explode);
                $formattedResults[$resourceKey] = json_encode(['id' => (int)$id]);
                continue;
            }

            $formattedResults[$resourceKey] = $resourceData;
        }

        return $formattedResults;
    }


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
                $query = "
      SELECT `key` as resource_key, `data` AS resource_data, `key` AS key_column, `alias_keys` AS alias_keys_column
        FROM $tableName
        WHERE `key` IN ( ";
                $query .= implode(',', $keys) . ' )';
                $selectFragments[] = $query;
                $query = "
      SELECT `alias_keys` as resource_key, `data` AS resource_data, `key` AS key_column, `alias_keys` AS alias_keys_column
        FROM $tableName
        WHERE `alias_keys` IN ( ";
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
