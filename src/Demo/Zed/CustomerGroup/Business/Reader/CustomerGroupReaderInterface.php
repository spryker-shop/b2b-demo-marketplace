<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CustomerGroup\Business\Reader;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;

interface CustomerGroupReaderInterface
{
    public function getCustomerGroupCollection(): CustomerGroupCollectionTransfer;
}
