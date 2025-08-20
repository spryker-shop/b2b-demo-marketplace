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
     * @param string $tenantColumnName
     *
     * @return array<array<string, mixed>>
     */
    public function getTablesWithTenantColumn(string $tenantColumnName): array
    {
        $sql = "
            SELECT
                TABLE_NAME as table_name,
                (SELECT COUNT(*) FROM information_schema.tables t2 WHERE t2.TABLE_NAME = c.TABLE_NAME AND t2.TABLE_SCHEMA = DATABASE()) as row_count,
                (SELECT COUNT(*) FROM information_schema.tables t3 WHERE t3.TABLE_NAME = c.TABLE_NAME AND t3.TABLE_SCHEMA = DATABASE()) as tenant_row_count,
                (SELECT COUNT(*) FROM information_schema.tables t4 WHERE t4.TABLE_NAME = c.TABLE_NAME AND t4.TABLE_SCHEMA = DATABASE()) as unassigned_row_count
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
            // Get actual row counts for each table
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
        $countSql = "SELECT COUNT(*) as total FROM `{$tableName}` {$whereClause}";
        $countStatement = $connection->prepare($countSql);
        foreach ($bindParams as $param => $value) {
            $countStatement->bindValue($param, $value);
        }
        $countStatement->execute();
        $totalCount = (int)$countStatement->fetchColumn();

        // Get rows
        $columnsList = implode(', ', array_map(function($col) { return "`{$col}`"; }, $columns));
        $sql = "SELECT {$columnsList} FROM `{$tableName}` {$whereClause} ORDER BY `{$primaryKey}` LIMIT :limit OFFSET :offset";

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
            $sql = "UPDATE `{$tableName}` SET `{$tenantColumnName}` = :tenantId WHERE `{$idColumnName}` = :rowId";

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
            $sourceDataSql = "SELECT * FROM `{$tableName}` WHERE `{$idColumnName}` = :sourceRowId LIMIT 1";
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
            $columnsSql = "
                SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :tableName
                AND COLUMN_NAME != :idColumnName
                ORDER BY ORDINAL_POSITION
            ";
            
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
                $columnNames[] = "`{$columnName}`";
                
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

            $insertSql = "INSERT INTO `{$tableName}` (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
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
     * @param string $tenantColumnName
     *
     * @return array<string, int>
     */
    protected function getTableRowCounts(string $tableName, string $tenantColumnName): array
    {
        $connection = Propel::getConnection();

        // Total rows
        $totalSql = "SELECT COUNT(*) FROM `{$tableName}`";
        $totalStatement = $connection->prepare($totalSql);
        $totalStatement->execute();
        $total = (int)$totalStatement->fetchColumn();

        // Rows with tenant
        $tenantSql = "SELECT COUNT(*) FROM `{$tableName}` WHERE `{$tenantColumnName}` IS NOT NULL";
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
}
