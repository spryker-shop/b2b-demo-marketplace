<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CustomerGroup\Business;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Spryker\Zed\CustomerGroup\Business\CustomerGroupFacadeInterface as SprykerCustomerGroupFacadeInterface;

interface CustomerGroupFacadeInterface extends SprykerCustomerGroupFacadeInterface
{
    /**
     * Specification:
     * - Retrieves the full collection of customer groups available in the system.
     *
     * @api
     */
    public function getCustomerGroupCollection(): CustomerGroupCollectionTransfer;
}
