<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business;

/**
 * @method \Pyz\Zed\TenantAssigner\Business\TenantAssignerBusinessFactory getFactory()
 * @method \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface getRepository()
 */
interface TenantAssignerFacadeInterface
{
    /**
     * Specification:
     * - Returns all database tables that contain the tenant column.
     * - Each table is represented as a TenantTableTransfer.
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\TenantTableTransfer>
     */
    public function getTablesWithTenantColumn(): array;

    /**
     * Specification:
     * - Retrieves rows from a specific table with pagination.
     * - Filters by tenant if specified in the request.
     * - Returns paginated results with total count.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTableRowsResponseTransfer
     */
    public function getTableRowsWithPagination(\Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer): \Generated\Shared\Transfer\TenantTableRowsResponseTransfer;

    /**
     * Specification:
     * - Assigns a tenant to a specific row in a table.
     * - Updates the tenant column for the specified row.
     * - Returns success/failure response.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantAssignmentTransfer $tenantAssignmentTransfer
     *
     * @return \Generated\Shared\Transfer\TenantAssignmentResponseTransfer
     */
    public function assignTenantToRow(\Generated\Shared\Transfer\TenantAssignmentTransfer $tenantAssignmentTransfer): \Generated\Shared\Transfer\TenantAssignmentResponseTransfer;

    /**
     * Specification:
     * - Duplicates an existing row and assigns it to a different tenant.
     * - Copies all data from the source row except the primary key.
     * - Sets the tenant column to the target tenant ID.
     * - Returns success/failure response with new row ID.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantDuplicationResponseTransfer
     */
    public function duplicateRowForTenant(\Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer): \Generated\Shared\Transfer\TenantDuplicationResponseTransfer;

    /**
     * Specification:
     * - Returns the list of available tenants from configuration.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getAvailableTenants(): array;
}
