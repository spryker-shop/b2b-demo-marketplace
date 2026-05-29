<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Checker;

use Generated\Shared\Transfer\CmsBlockTransfer;

interface CmsBlockCustomerGroupCheckerInterface
{
    public function hasCustomerAccessToCmsBlock(CmsBlockTransfer $cmsBlockTransfer, int $idCustomer): bool;
}
