<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business\Service;

use Generated\Shared\Transfer\TenantTableTransfer;
use Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface;
use Pyz\Zed\TenantAssigner\TenantAssignerConfig;

class TableDiscoveryService implements TableDiscoveryServiceInterface
{
    /**
     * @param \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface $repository
     * @param \Pyz\Zed\TenantAssigner\TenantAssignerConfig $config
     */
    public function __construct(
        protected TenantAssignerRepositoryInterface $repository,
        protected TenantAssignerConfig $config,
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\TenantTableTransfer>
     */
    public function getTablesWithTenantColumn(): array
    {
        $tablesWithTenantColumn = $this->repository->getTablesWithTenantColumn(
            $this->config->getTenantColumnName(),
        );

        $tenantTableTransfers = [];
        foreach ($tablesWithTenantColumn as $tableData) {
            $tenantTableTransfer = new TenantTableTransfer();
            $tenantTableTransfer->setTableName($tableData['table_name']);
            $tenantTableTransfer->setDisplayName($this->formatTableDisplayName($tableData['table_name']));
            $tenantTableTransfer->setRowCount($tableData['row_count'] ?? 0);
            $tenantTableTransfer->setTenantRowCount($tableData['tenant_row_count'] ?? 0);
            $tenantTableTransfer->setUnassignedRowCount($tableData['unassigned_row_count'] ?? 0);

            $tenantTableTransfers[] = $tenantTableTransfer;
        }

        return $tenantTableTransfers;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function formatTableDisplayName(string $tableName): string
    {
        // Convert snake_case to Title Case
        $displayName = str_replace('_', ' ', $tableName);
        $displayName = str_replace('spy ', '', $displayName);
        
        return ucwords($displayName);
    }
}
