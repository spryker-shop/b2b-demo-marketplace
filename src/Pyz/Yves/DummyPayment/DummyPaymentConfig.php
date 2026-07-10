<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\DummyPayment;

use Spryker\Yves\DummyPayment\DummyPaymentConfig as SprykerDummyPaymentConfig;

class DummyPaymentConfig extends SprykerDummyPaymentConfig
{
    public function isDateOfBirthEnabled(): bool
    {
        return false;
    }
}
