<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence;

use Demo\Zed\CmsBlockCustomerGroup\Persistence\Propel\Mapper\CustomerGroupMapper;
use Orm\Zed\CmsBlockCustomerGroup\Persistence\PyzCmsBlockCustomerGroup;
use Orm\Zed\CmsBlockCustomerGroup\Persistence\PyzCmsBlockCustomerGroupQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface getRepository()
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupEntityManagerInterface getEntityManager()
 */
class CmsBlockCustomerGroupPersistenceFactory extends AbstractPersistenceFactory
{
    public function createCmsBlockCustomerGroupQuery(): PyzCmsBlockCustomerGroupQuery
    {
        return PyzCmsBlockCustomerGroupQuery::create();
    }

    public function createPyzCmsBlockCustomerGroupEntity(): PyzCmsBlockCustomerGroup
    {
        return new PyzCmsBlockCustomerGroup();
    }

    public function createCustomerGroupMapper(): CustomerGroupMapper
    {
        return new CustomerGroupMapper();
    }
}
