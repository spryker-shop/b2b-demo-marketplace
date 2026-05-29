<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CustomerGroup\Business\Reader;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use Spryker\Zed\CustomerGroup\Persistence\CustomerGroupQueryContainerInterface;

class CustomerGroupReader implements CustomerGroupReaderInterface
{
    public function __construct(protected CustomerGroupQueryContainerInterface $customerGroupQueryContainer)
    {
    }

    public function getCustomerGroupCollection(): CustomerGroupCollectionTransfer
    {
        $customerGroupCollectionTransfer = new CustomerGroupCollectionTransfer();
        $customerGroupEntities = $this->customerGroupQueryContainer
            ->queryCustomerGroup()
            ->find();

        foreach ($customerGroupEntities as $customerGroupEntity) {
            $customerGroupCollectionTransfer->addGroup(
                (new CustomerGroupTransfer())
                    ->setIdCustomerGroup($customerGroupEntity->getIdCustomerGroup())
                    ->setName($customerGroupEntity->getName()),
            );
        }

        return $customerGroupCollectionTransfer;
    }
}
