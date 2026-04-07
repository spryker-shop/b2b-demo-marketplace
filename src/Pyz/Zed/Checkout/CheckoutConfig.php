<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Pyz\Zed\Checkout;

use Spryker\Zed\Checkout\CheckoutConfig as SprykerCheckoutConfig;
use Spryker\Zed\Sales\Business\Exception\DuplicateOrderReferenceException;

class CheckoutConfig extends SprykerCheckoutConfig
{
    /**
     * @return list<string>
     */
    public function getRetryableExceptions(): array
    {
        return [
            DuplicateOrderReferenceException::class,
        ];
    }
}