<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Business\Service;

use Generated\Shared\Transfer\TenantDuplicationResponseTransfer;
use Generated\Shared\Transfer\TenantDuplicationTransfer;

interface TenantDuplicationServiceInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantDuplicationResponseTransfer
     */
    public function duplicateRowForTenant(TenantDuplicationTransfer $tenantDuplicationTransfer): TenantDuplicationResponseTransfer;
}
