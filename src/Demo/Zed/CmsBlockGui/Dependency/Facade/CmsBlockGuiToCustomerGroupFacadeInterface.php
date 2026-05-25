<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Dependency\Facade;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;

interface CmsBlockGuiToCustomerGroupFacadeInterface
{
    public function getCustomerGroupCollection(): CustomerGroupCollectionTransfer;
}
