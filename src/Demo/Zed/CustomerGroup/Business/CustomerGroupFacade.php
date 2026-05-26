<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CustomerGroup\Business;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Spryker\Zed\CustomerGroup\Business\CustomerGroupFacade as SprykerCustomerGroupFacade;

/**
 * @method \Demo\Zed\CustomerGroup\Business\CustomerGroupBusinessFactory getFactory()
 * @method \Spryker\Zed\CustomerGroup\Persistence\CustomerGroupRepositoryInterface getRepository()
 */
class CustomerGroupFacade extends SprykerCustomerGroupFacade implements CustomerGroupFacadeInterface
{
    public function getCustomerGroupCollection(): CustomerGroupCollectionTransfer
    {
        return $this->getFactory()
            ->createCustomerGroupReader()
            ->getCustomerGroupCollection();
    }
}
