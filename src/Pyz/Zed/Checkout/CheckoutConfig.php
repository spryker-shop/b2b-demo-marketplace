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
    protected const int DRIVER_CODE_SNAPSHOT_CONFLICT = 1020;

    protected const int DRIVER_CODE_DEADLOCK = 1213;

    /**
     * @return list<string>
     */
    public function getRetryableExceptions(): array
    {
        return [
            DuplicateOrderReferenceException::class,
        ];
    }

    /**
     * @return list<int>
     */
    public function getRetryableDriverErrorCodes(): array
    {
        return [
            static::DRIVER_CODE_SNAPSHOT_CONFLICT,
            static::DRIVER_CODE_DEADLOCK,
        ];
    }
}
