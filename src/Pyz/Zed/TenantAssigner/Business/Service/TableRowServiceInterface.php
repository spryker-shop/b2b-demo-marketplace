<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business\Service;

interface TableRowServiceInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTableRowsResponseTransfer
     */
    public function getTableRowsWithPagination(\Generated\Shared\Transfer\TenantTableRowsRequestTransfer $tenantTableRowsRequestTransfer): \Generated\Shared\Transfer\TenantTableRowsResponseTransfer;
}
