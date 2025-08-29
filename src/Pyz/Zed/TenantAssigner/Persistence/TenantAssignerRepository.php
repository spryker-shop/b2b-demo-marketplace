<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Persistence;

use Propel\Runtime\Propel;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerPersistenceFactory getFactory()
 */
class TenantAssignerRepository extends AbstractRepository implements TenantAssignerRepositoryInterface
{
    /**
     * @return string
     */
    protected function getDatabaseEngine(): string
    {
        $connection = Propel::getConnection();
        $driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        return $driver;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return $this->getDatabaseEngine() === 'mysql' ? "`{$identifier}`" : "\"{$identifier}\"";
    }

    /**
     * @return string
     */
    protected function getCurrentDatabaseName(): string
    {
        $connection = Propel::getConnection();
        
        if ($this->getDatabaseEngine() === 'mysql') {
            return 'DATABASE()';
        }
        
        // For PostgreSQL, get current database name
        $stmt = $connection->query('SELECT current_database()');
        $dbName = $stmt->fetchColumn();
        
        return "'{$dbName}'";
    }
    /**
     * @param string $tenantColumnName
     *
     * @return array<array<string, mixed>>
     */
    public function getTablesWithTenantColumn(string $tenantColumnName): array
    {
        if ($this->getDatabaseEngine() === 'mysql') {
            return $this->getTablesWithTenantColumnMySQL($tenantColumnName);
        }
        
        return $this->getTablesWithTenantColumnPostgreSQL($tenantColumnName);
    }

    /**
     * @param string $tenantColumnName
     *
     * @return array<array<string, mixed>>
     */
    protected function getTablesWithTenantColumnMySQL(string $tenantColumnName): array
    {
        $sql = "
            SELECT
                TABLE_NAME as table_name
            FROM information_schema.COLUMNS c
            WHERE c.TABLE_SCHEMA = DATABASE()
            AND c.COLUMN_NAME = :tenantColumnName
            ORDER BY c.TABLE_NAME
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tenantColumnName', $tenantColumnName);
        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $tableName = $row['table_name'];
            $rowCounts = $this->getTableRowCounts($tableName, $tenantColumnName);

            $results[] = [
                'table_name' => $tableName,
                'row_count' => $rowCounts['total'],
                'tenant_row_count' => $rowCounts['with_tenant'],
                'unassigned_row_count' => $rowCounts['unassigned'],
            ];
        }

        return $results;
    }

    /**
     * @param string $tenantColumnName
     *
     * @return array<array<string, mixed>>
     */
    protected function getTablesWithTenantColumnPostgreSQL(string $tenantColumnName): array
    {
        $sql = "
            SELECT
                table_name
            FROM information_schema.columns c
            WHERE c.table_catalog = current_database()
            AND c.table_schema = 'public'
            AND c.column_name = :tenantColumnName
            ORDER BY c.table_name
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tenantColumnName', $tenantColumnName);
        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $tableName = $row['table_name'];
            $rowCounts = $this->getTableRowCounts($tableName, $tenantColumnName);

            $results[] = [
                'table_name' => $tableName,
                'row_count' => $rowCounts['total'],
                'tenant_row_count' => $rowCounts['with_tenant'],
                'unassigned_row_count' => $rowCounts['unassigned'],
            ];
        }

        return $results;
    }

    /**
     * @param string $tableName
     * @param int $offset
     * @param int $limit
     * @param string|null $tenantFilter
     * @param bool $showUnassignedOnly
     *
     * @return array<string, mixed>
     */
    public function getTableRowsWithPagination(
        string $tableName,
        int $offset,
        int $limit,
        ?string $tenantFilter = null,
        bool $showUnassignedOnly = false,
    ): array {
        $connection = Propel::getConnection();

        // Get table columns first
        $columns = $this->getDisplayableColumns($tableName);
        $primaryKey = $this->getTablePrimaryKeyColumn($tableName);

        // Build WHERE clause
        $whereConditions = [];
        $bindParams = [];

        if ($showUnassignedOnly) {
            $whereConditions[] = 'id_tenant IS NULL';
        } elseif ($tenantFilter) {
            $whereConditions[] = 'id_tenant = :tenantFilter';
            $bindParams[':tenantFilter'] = $tenantFilter;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $tableNameQuoted = $this->quoteIdentifier($tableName);
        $countSql = "SELECT COUNT(*) as total FROM {$tableNameQuoted} {$whereClause}";
        $countStatement = $connection->prepare($countSql);
        foreach ($bindParams as $param => $value) {
            $countStatement->bindValue($param, $value);
        }
        $countStatement->execute();
        $totalCount = (int)$countStatement->fetchColumn();

        // Get rows
        $columnsList = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));
        $tableNameQuoted = $this->quoteIdentifier($tableName);
        $primaryKeyQuoted = $this->quoteIdentifier($primaryKey);
        $sql = "SELECT {$columnsList} FROM {$tableNameQuoted} {$whereClause} ORDER BY {$primaryKeyQuoted} LIMIT :limit OFFSET :offset";

        $statement = $connection->prepare($sql);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        foreach ($bindParams as $param => $value) {
            $statement->bindValue($param, $value);
        }
        $statement->execute();

        $rows = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'total_count' => $totalCount,
            'columns' => $columns,
            'primary_key' => $primaryKey,
        ];
    }

    /**
     * @param string $tableName
     * @param string $tenantColumnName
     *
     * @return array<string>
     */
    public function getTableColumns(string $tableName, string $tenantColumnName): array
    {
        return $this->getDisplayableColumns($tableName);
    }

    /**
     * @param string $tableName
     * @param string $rowId
     * @param string $tenantId
     * @param string $idColumnName
     * @param string $tenantColumnName
     *
     * @return bool
     */
    public function assignTenantToRow(
        string $tableName,
        string $rowId,
        string $tenantId,
        string $idColumnName,
        string $tenantColumnName,
    ): bool {
        try {
            $connection = Propel::getConnection();
            $tableNameQuoted = $this->quoteIdentifier($tableName);
            $tenantColumnQuoted = $this->quoteIdentifier($tenantColumnName);
            $idColumnQuoted = $this->quoteIdentifier($idColumnName);
            
            $sql = "UPDATE {$tableNameQuoted} SET {$tenantColumnQuoted} = :tenantId WHERE {$idColumnQuoted} = :rowId";

            $statement = $connection->prepare($sql);
            $statement->bindValue(':tenantId', $tenantId);
            $statement->bindValue(':rowId', $rowId);

            return $statement->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $tableName
     * @param string $sourceRowId
     * @param string $targetTenantId
     * @param string $idColumnName
     * @param string $tenantColumnName
     *
     * @return array{success: bool, newRowId: string|null, error: string|null}
     */
    public function duplicateRowForTenant(
        string $tableName,
        string $sourceRowId,
        string $targetTenantId,
        string $idColumnName,
        string $tenantColumnName,
    ): array {
        try {
            $connection = Propel::getConnection();

            // First, get the column structure and data of the source row
            $tableNameQuoted = $this->quoteIdentifier($tableName);
            $idColumnQuoted = $this->quoteIdentifier($idColumnName);
            
            $sourceDataSql = "SELECT * FROM {$tableNameQuoted} WHERE {$idColumnQuoted} = :sourceRowId LIMIT 1";
            $sourceStatement = $connection->prepare($sourceDataSql);
            $sourceStatement->bindValue(':sourceRowId', $sourceRowId);
            $sourceStatement->execute();
            
            $sourceData = $sourceStatement->fetch(\PDO::FETCH_ASSOC);
            if (!$sourceData) {
                return [
                    'success' => false,
                    'newRowId' => null,
                    'error' => 'Source row not found'
                ];
            }

            // Get all columns except the primary key and tenant column
            if ($this->getDatabaseEngine() === 'mysql') {
                $columnsSql = "
                    SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = :tableName
                    AND COLUMN_NAME != :idColumnName
                    ORDER BY ORDINAL_POSITION
                ";
            } else {
                $columnsSql = "
                    SELECT column_name as COLUMN_NAME, column_default as COLUMN_DEFAULT, 
                           is_nullable as IS_NULLABLE, data_type as DATA_TYPE
                    FROM information_schema.columns
                    WHERE table_catalog = current_database()
                    AND table_schema = 'public'
                    AND table_name = :tableName
                    AND column_name != :idColumnName
                    ORDER BY ordinal_position
                ";
            }
            
            $columnsStatement = $connection->prepare($columnsSql);
            $columnsStatement->bindValue(':tableName', $tableName);
            $columnsStatement->bindValue(':idColumnName', $idColumnName);
            $columnsStatement->execute();
            
            $columns = $columnsStatement->fetchAll(\PDO::FETCH_ASSOC);

            // Prepare INSERT statement
            $columnNames = [];
            $placeholders = [];
            $values = [];

            foreach ($columns as $column) {
                $columnName = $column['COLUMN_NAME'];
                $columnNames[] = $this->quoteIdentifier($columnName);
                
                if ($columnName === $tenantColumnName) {
                    // Set the target tenant ID
                    $placeholders[] = ':targetTenantId';
                    $values[':targetTenantId'] = $targetTenantId;
                } else {
                    // Copy the value from source row
                    $placeholder = ":{$columnName}";
                    $placeholders[] = $placeholder;
                    $values[$placeholder] = $sourceData[$columnName];
                }
            }

            $insertSql = "INSERT INTO {$tableNameQuoted} (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $insertStatement = $connection->prepare($insertSql);
            foreach ($values as $placeholder => $value) {
                $insertStatement->bindValue($placeholder, $value);
            }

            if ($insertStatement->execute()) {
                $newRowId = $connection->lastInsertId();
                return [
                    'success' => true,
                    'newRowId' => (string)$newRowId,
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'newRowId' => null,
                    'error' => 'Failed to insert duplicate row'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'newRowId' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getTablePrimaryKeyColumn(string $tableName): string
    {
        if ($this->getDatabaseEngine() === 'mysql') {
            return $this->getTablePrimaryKeyColumnMySQL($tableName);
        }
        
        return $this->getTablePrimaryKeyColumnPostgreSQL($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function getTablePrimaryKeyColumnMySQL(string $tableName): string
    {
        $sql = "
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :tableName
            AND COLUMN_KEY = 'PRI'
            LIMIT 1
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tableName', $tableName);
        $statement->execute();

        $result = $statement->fetchColumn();

        return $result ?: 'id';
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function getTablePrimaryKeyColumnPostgreSQL(string $tableName): string
    {
        $sql = "
            SELECT a.attname as column_name
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE i.indrelid = :tableName::regclass
            AND i.indisprimary
            LIMIT 1
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tableName', $tableName);
        $statement->execute();

        $result = $statement->fetchColumn();

        return $result ?: 'id';
    }

    /**
     * @param string $tableName
     * @param string $tenantColumnName
     *
     * @return array<string, int>
     */
    protected function getTableRowCounts(string $tableName, string $tenantColumnName): array
    {
        $connection = Propel::getConnection();
        $tableNameQuoted = $this->quoteIdentifier($tableName);
        $tenantColumnQuoted = $this->quoteIdentifier($tenantColumnName);

        // Total rows
        $totalSql = "SELECT COUNT(*) FROM {$tableNameQuoted}";
        $totalStatement = $connection->prepare($totalSql);
        $totalStatement->execute();
        $total = (int)$totalStatement->fetchColumn();

        // Rows with tenant
        $tenantSql = "SELECT COUNT(*) FROM {$tableNameQuoted} WHERE {$tenantColumnQuoted} IS NOT NULL";
        $tenantStatement = $connection->prepare($tenantSql);
        $tenantStatement->execute();
        $withTenant = (int)$tenantStatement->fetchColumn();

        return [
            'total' => $total,
            'with_tenant' => $withTenant,
            'unassigned' => $total - $withTenant,
        ];
    }

    /**
     * @param string $tableName
     *
     * @return array<string>
     */
    protected function getDisplayableColumns(string $tableName): array
    {
        if ($this->getDatabaseEngine() === 'mysql') {
            return $this->getDisplayableColumnsMySQL($tableName);
        }
        
        return $this->getDisplayableColumnsPostgreSQL($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return array<string>
     */
    protected function getDisplayableColumnsMySQL(string $tableName): array
    {
        $sql = "
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :tableName
            AND COLUMN_NAME NOT IN ('created_at', 'updated_at')
            ORDER BY ORDINAL_POSITION
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tableName', $tableName);
        $statement->execute();

        $columns = [];
        while ($column = $statement->fetchColumn()) {
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @param string $tableName
     *
     * @return array<string>
     */
    protected function getDisplayableColumnsPostgreSQL(string $tableName): array
    {
        $sql = "
            SELECT column_name
            FROM information_schema.columns
            WHERE table_catalog = current_database()
            AND table_schema = 'public'
            AND table_name = :tableName
            AND column_name NOT IN ('created_at', 'updated_at')
            ORDER BY ordinal_position
        ";

        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':tableName', $tableName);
        $statement->execute();

        $columns = [];
        while ($column = $statement->fetchColumn()) {
            $columns[] = $column;
        }

        return $columns;
    }
}
