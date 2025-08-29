<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business;

use Generated\Shared\Transfer\TenantAssignmentResponseTransfer;
use Generated\Shared\Transfer\TenantAssignmentTransfer;
use Generated\Shared\Transfer\TenantDuplicationResponseTransfer;
use Generated\Shared\Transfer\TenantDuplicationTransfer;
use Generated\Shared\Transfer\TenantTableRowsRequestTransfer;
use Generated\Shared\Transfer\TenantTableRowsResponseTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\TenantAssigner\Business\TenantAssignerBusinessFactory getFactory()
 * @method \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface getRepository()
 */
class TenantAssignerFacade extends AbstractFacade implements TenantAssignerFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\TenantTableTransfer>
     */
    public function getTablesWithTenantColumn(): array
    {
        return $this->getFactory()
            ->createTableDiscoveryService()
            ->getTablesWithTenantColumn();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTableRowsResponseTransfer
     */
    public function getTableRowsWithPagination(TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer): TenantTableRowsResponseTransfer
    {
        return $this->getFactory()
            ->createTableRowService()
            ->getTableRowsWithPagination($tenantTableRowsRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantAssignmentTransfer $tenantAssignmentTransfer
     *
     * @return \Generated\Shared\Transfer\TenantAssignmentResponseTransfer
     */
    public function assignTenantToRow(TenantAssignmentTransfer $tenantAssignmentTransfer): TenantAssignmentResponseTransfer
    {
        return $this->getFactory()
            ->createTenantAssignmentService()
            ->assignTenantToRow($tenantAssignmentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantDuplicationResponseTransfer
     */
    public function duplicateRowForTenant(TenantDuplicationTransfer $tenantDuplicationTransfer): TenantDuplicationResponseTransfer
    {
        return $this->getFactory()
            ->createTenantDuplicationService()
            ->duplicateRowForTenant($tenantDuplicationTransfer);
    }
}
