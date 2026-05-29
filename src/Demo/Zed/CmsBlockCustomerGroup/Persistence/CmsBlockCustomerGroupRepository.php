<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Orm\Zed\CmsBlockCustomerGroup\Persistence\Map\PyzCmsBlockCustomerGroupTableMap;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupPersistenceFactory getFactory()
 */
class CmsBlockCustomerGroupRepository extends AbstractRepository implements CmsBlockCustomerGroupRepositoryInterface
{
    /**
     * @param int $idCmsBlock
     *
     * @return array<int>
     */
    public function getCmsBlockCustomerGroupIds(int $idCmsBlock): array
    {
        return $this->getFactory()
            ->createCmsBlockCustomerGroupQuery()
            ->filterByFkCmsBlock($idCmsBlock)
            ->select(PyzCmsBlockCustomerGroupTableMap::COL_FK_CUSTOMER_GROUP)
            ->find()
            ->getData();
    }

    public function getCmsBlockCustomerGroups(int $idCmsBlock): CustomerGroupCollectionTransfer
    {
        $customerGroupIds = $this->getCmsBlockCustomerGroupIds($idCmsBlock);
        $customerGroupCollectionTransfer = new CustomerGroupCollectionTransfer();

        if (!count($customerGroupIds)) {
            return $customerGroupCollectionTransfer;
        }

        return $this->getFactory()
            ->createCustomerGroupMapper()
            ->mapCustomerGroupIdsToCustomerGroupCollection($customerGroupIds, $customerGroupCollectionTransfer);
    }

    public function hasCustomerInCmsBlockCustomerGroups(int $idCmsBlock, int $idCustomer): bool
    {
        return $this->getFactory()
            ->createCmsBlockCustomerGroupQuery()
            ->filterByFkCmsBlock($idCmsBlock)
            ->useSpyCustomerGroupQuery()
                ->useSpyCustomerGroupToCustomerQuery()
                    ->filterByFkCustomer($idCustomer)
                ->endUse()
            ->endUse()
            ->exists();
    }
}
