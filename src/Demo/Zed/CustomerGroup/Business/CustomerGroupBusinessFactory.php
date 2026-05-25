<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CustomerGroup\Business;

use Demo\Zed\CustomerGroup\Business\Reader\CustomerGroupReader;
use Demo\Zed\CustomerGroup\Business\Reader\CustomerGroupReaderInterface;
use Spryker\Zed\CustomerGroup\Business\CustomerGroupBusinessFactory as SprykerCustomerGroupBusinessFactory;

/**
 * @method \Spryker\Zed\CustomerGroup\CustomerGroupConfig getConfig()
 * @method \Spryker\Zed\CustomerGroup\Persistence\CustomerGroupRepositoryInterface getRepository()
 */
class CustomerGroupBusinessFactory extends SprykerCustomerGroupBusinessFactory
{
    public function createCustomerGroupReader(): CustomerGroupReaderInterface
    {
        return new CustomerGroupReader($this->getQueryContainer());
    }
}
