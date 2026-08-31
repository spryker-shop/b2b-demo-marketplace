<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

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
