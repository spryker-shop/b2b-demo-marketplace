<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\CustomerAccess\Persistence;

use Orm\Zed\CustomerAccess\Persistence\SpyUnauthenticatedCustomerAccessQuery;
use Spryker\Zed\CustomerAccess\Persistence\CustomerAccessPersistenceFactory as SprykerCustomerAccessPersistenceFactory;

/**
 * @method \Pyz\Zed\CustomerAccess\Persistence\CustomerAccessEntityManagerInterface getEntityManager()
 * @method \Pyz\Zed\CustomerAccess\CustomerAccessConfig getConfig()
 * @method \Pyz\Zed\CustomerAccess\Persistence\CustomerAccessRepositoryInterface getRepository()
 */
class CustomerAccessPersistenceFactory extends SprykerCustomerAccessPersistenceFactory
{
    /**
     * @return \Orm\Zed\CustomerAccess\Persistence\SpyUnauthenticatedCustomerAccessQuery
     */
    public function getUnauthenticatedCustomerAccessQuery(): SpyUnauthenticatedCustomerAccessQuery
    {
        return SpyUnauthenticatedCustomerAccessQuery::create();
    }
}
