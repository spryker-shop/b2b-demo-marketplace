<?php

namespace Pyz\Client\StorageDatabase\Storage\Reader;

use Propel\Runtime\Connection\StatementInterface;
use Spryker\Client\StorageDatabase\Storage\Reader\PostgreSqlStorageReader as SprykerPostgreSqlStorageReader;

class PostgreSqlStorageReader extends SprykerPostgreSqlStorageReader
{
    protected const SELECT_STATEMENT_PATTERN = 'SELECT %1$s as resource_key, "data" AS resource_data, "key" AS key_column, "alias_keys" AS alias_keys_column FROM %2$s WHERE "key" = %1$s OR alias_keys = %1$s';

    /**
     * @param string $resourceKey
     *
     * @return \Propel\Runtime\Connection\StatementInterface
     */
    protected function createSingleSelectStatementForResourceKey(string $resourceKey): StatementInterface
    {
        $tableName = $this->tableNameResolver->resolveByResourceKey($resourceKey);
        $selectSqlString = $this->buildSelectQuerySql($tableName);
        $statement = $this->createPreparedStatement($selectSqlString);
        $statement->bindValue(static::DEFAULT_PLACEHOLDER_KEY, $resourceKey);

        return $statement;
    }

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
      SELECT "key" as resource_key, "data" AS resource_data, "key" AS key_column, "alias_keys" AS alias_keys_column
        FROM ' . $tableName . '
        WHERE "key" IN ( ';
                $query .= implode(', ', $keys) . ' )';
                $selectFragments[] = $query;
                $query = '
      SELECT "alias_keys" as resource_key, "data" AS resource_data, "key" AS "key_column", "alias_keys" AS "alias_keys_column"
        FROM ' . $tableName . '
        WHERE "alias_keys" IN ( ';
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
            if ((int)$id == $id) {
                $id = (int)$id;
            }
            return json_encode(['id' => $id]);
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
                if ((int)$id == $id) {
                    $id = (int)$id;
                }
                $formattedResults[$resourceKey] = json_encode(['id' => $id]);
                continue;
            }

            $formattedResults[$resourceKey] = $resourceData;
        }

        return $formattedResults;
    }

    /**
     * @param string $tableName
     * @param string $keyPlaceholder
     *
     * @return string
     */
    protected function buildSelectQuerySql(string $tableName, string $keyPlaceholder = self::DEFAULT_PLACEHOLDER_KEY): string
    {
        $sql = <<<SQL
SELECT %s::VARCHAR AS resource_key,
       "data" AS resource_data, "key" AS key_column, "alias_keys" AS alias_keys_column
FROM %s
WHERE "key" = %s::VARCHAR
   OR "alias_keys" = %s::VARCHAR
SQL;
        return sprintf(
            $sql,
            $keyPlaceholder,
            $tableName,
            $keyPlaceholder,
            $keyPlaceholder,
        );
    }

    /**
     * @param int|string $index
     *
     * @return string
     */
    protected function buildKeyPlaceholder(int|string $index = 0): string
    {
        return sprintf(':%s%d', static::FIELD_KEY, $index);
    }
}
