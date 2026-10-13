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
     * MariaDB ER_CHECKREAD, "Record has changed since last read in table '%s'". Raised when
     * innodb_snapshot_isolation is ON (the default from 11.6.2) and a locking read inside the
     * order-save transaction hits a row another transaction committed after this one took its
     * snapshot. The losing transaction has to start over, which is what this retry loop does.
     */
    protected const int DRIVER_CODE_SNAPSHOT_CONFLICT = 1020;

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
        ];
    }
}
