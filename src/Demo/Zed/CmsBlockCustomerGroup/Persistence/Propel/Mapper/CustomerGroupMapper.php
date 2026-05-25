<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;

class CustomerGroupMapper
{
    /**
     * @param array<int> $customerGroupIds
     * @param \Generated\Shared\Transfer\CustomerGroupCollectionTransfer $customerGroupCollectionTransfer
     *
     * @return \Generated\Shared\Transfer\CustomerGroupCollectionTransfer
     */
    public function mapCustomerGroupIdsToCustomerGroupCollection(
        array $customerGroupIds,
        CustomerGroupCollectionTransfer $customerGroupCollectionTransfer,
    ): CustomerGroupCollectionTransfer {
        foreach ($customerGroupIds as $idCustomerGroup) {
            $customerGroupCollectionTransfer->addGroup(
                (new CustomerGroupTransfer())->setIdCustomerGroup($idCustomerGroup),
            );
        }

        return $customerGroupCollectionTransfer;
    }
}
