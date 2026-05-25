<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence;

use Orm\Zed\CmsBlockCustomerGroup\Persistence\PyzCmsBlockCustomerGroup;
use Orm\Zed\CmsBlockCustomerGroup\Persistence\PyzCmsBlockCustomerGroupQuery;
use Propel\Runtime\Collection\ObjectCollection;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupPersistenceFactory getFactory()
 */
class CmsBlockCustomerGroupEntityManager extends AbstractEntityManager implements CmsBlockCustomerGroupEntityManagerInterface
{
    /**
     * @param array<int> $customerGroupIds
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function createCmsBlockCustomerGroups(array $customerGroupIds, int $idCmsBlock): void
    {
        if (!count($customerGroupIds)) {
            return;
        }

        $pyzCmsBlockCustomerGroupEntityCollection = new ObjectCollection();
        $pyzCmsBlockCustomerGroupEntityCollection->setModel(PyzCmsBlockCustomerGroup::class);

        foreach ($customerGroupIds as $idCustomerGroup) {
            $pyzCmsBlockCustomerGroupEntity = $this->getFactory()
                ->createPyzCmsBlockCustomerGroupEntity()
                ->setFkCustomerGroup($idCustomerGroup)
                ->setFkCmsBlock($idCmsBlock);

            $pyzCmsBlockCustomerGroupEntityCollection->append($pyzCmsBlockCustomerGroupEntity);
        }

        $pyzCmsBlockCustomerGroupEntityCollection->save();
    }

    /**
     * @param array<int> $customerGroupIds
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function deleteCmsBlockCustomerGroups(array $customerGroupIds, int $idCmsBlock): void
    {
        if (!count($customerGroupIds)) {
            return;
        }

        PyzCmsBlockCustomerGroupQuery::create()
            ->filterByFkCmsBlock($idCmsBlock)
            ->filterByFkCustomerGroup_In($customerGroupIds)
            ->delete();
    }
}
