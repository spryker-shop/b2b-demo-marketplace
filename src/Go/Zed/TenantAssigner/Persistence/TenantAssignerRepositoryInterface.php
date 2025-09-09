<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Persistence;

interface TenantAssignerRepositoryInterface
{
    /**
     * @param string $tenantColumnName
     *
     * @return array<array<string, mixed>>
     */
    public function getTablesWithTenantColumn(string $tenantColumnName): array;

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
    ): array;

    /**
     * @param string $tableName
     * @param string $tenantColumnName
     *
     * @return array<string>
     */
    public function getTableColumns(string $tableName, string $tenantColumnName): array;

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
    ): bool;

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
    ): array;

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getTablePrimaryKeyColumn(string $tableName): string;
}
