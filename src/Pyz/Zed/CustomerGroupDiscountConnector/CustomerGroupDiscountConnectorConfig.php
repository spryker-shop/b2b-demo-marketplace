<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerGroupDiscountConnector;

use Spryker\Zed\CustomerGroupDiscountConnector\CustomerGroupDiscountConnectorConfig as SprykerCustomerGroupDiscountConnectorConfig;

class CustomerGroupDiscountConnectorConfig extends SprykerCustomerGroupDiscountConnectorConfig
{
    public function isNegatedOperatorAppliedToAllCustomerGroups(): bool
    {
        return true;
    }
}
