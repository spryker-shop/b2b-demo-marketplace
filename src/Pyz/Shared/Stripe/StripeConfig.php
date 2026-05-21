<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\Stripe;

use SprykerEco\Shared\Stripe\StripeConfig as SprykerEcoStripeConfig;

class StripeConfig extends SprykerEcoStripeConfig
{
    public function isConfigurationModuleUsed(): bool
    {
        return true;
    }
}
