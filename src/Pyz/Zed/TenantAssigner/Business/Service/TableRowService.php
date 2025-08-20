<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business\Service;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\TenantTableRowsRequestTransfer;
use Generated\Shared\Transfer\TenantTableRowsResponseTransfer;
use Generated\Shared\Transfer\TenantTableRowTransfer;
use Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface;
use Pyz\Zed\TenantAssigner\TenantAssignerConfig;

class TableRowService implements TableRowServiceInterface
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
     * @param \Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTableRowsResponseTransfer
     */
    public function getTableRowsWithPagination(TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer): TenantTableRowsResponseTransfer
    {
        $pageSize = min(
            $tenantTableRowsRequestTransfer->getPageSize() ?: $this->config->getDefaultPageSize(),
            $this->config->getMaxPageSize(),
        );
        $page = max(1, $tenantTableRowsRequestTransfer->getPage() ?: 1);
        $offset = ($page - 1) * $pageSize;

        $result = $this->repository->getTableRowsWithPagination(
            $tenantTableRowsRequestTransfer->getTableName(),
            $offset,
            $pageSize,
            $tenantTableRowsRequestTransfer->getTenantFilter(),
            $tenantTableRowsRequestTransfer->getShowUnassignedOnly(),
        );

        $response = new TenantTableRowsResponseTransfer();
        $response->setTableName($tenantTableRowsRequestTransfer->getTableName());
        $response->setColumns($result['columns']);

        // Convert rows to transfer objects
        $rowTransfers = [];
        foreach ($result['rows'] as $rowData) {
            $rowTransfer = new TenantTableRowTransfer();
            $rowTransfer->setId((string)$rowData[$result['primary_key']]);
            $rowTransfer->setData($rowData);
            $rowTransfer->setTenantId($rowData[$this->config->getTenantColumnName()] ?? null);
            
            // Set display columns (first few important columns)
            $displayColumns = $this->getDisplayColumns($rowData, $result['columns']);
            $rowTransfer->setDisplayColumns($displayColumns);
            
            $rowTransfers[] = $rowTransfer;
        }
        $response->setRows(new \ArrayObject($rowTransfers));

        // Create pagination
        $totalPages = (int)ceil($result['total_count'] / $pageSize);
        $pagination = new PaginationTransfer();
        $pagination->setPage($page);
        $pagination->setMaxPerPage($pageSize);
        $pagination->setNbResults($result['total_count']);
        $pagination->setFirstPage(1);
        $pagination->setLastPage($totalPages);
        $pagination->setPreviousPage($page > 1 ? $page - 1 : null);
        $pagination->setNextPage($page < $totalPages ? $page + 1 : null);
        $pagination->setFirstIndex($offset + 1);
        $pagination->setLastIndex(min($offset + $pageSize, $result['total_count']));

        $response->setPagination($pagination);

        return $response;
    }

    /**
     * @param array<string, mixed> $rowData
     * @param array<string> $columns
     *
     * @return array<string, mixed>
     */
    protected function getDisplayColumns(array $rowData, array $columns): array
    {
        $displayColumns = [];
        $maxDisplayColumns = 5;
        $count = 0;

        foreach ($columns as $column) {
            if ($count >= $maxDisplayColumns) {
                break;
            }
            
            // Skip certain columns from display
            if (in_array($column, ['created_at', 'updated_at', 'id_tenant'], true)) {
                continue;
            }

            $displayColumns[$column] = $rowData[$column] ?? '';
            $count++;
        }

        return $displayColumns;
    }
}
