<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class FreeShippingHintConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Returns the order value (in cents, after discounts) that unlocks free shipping, indexed by currency ISO code.
     * - Currencies without an entry get no free shipping hint.
     *
     * @api
     *
     * @return array<string, int>
     */
    public function getFreeShippingThresholdsPerCurrency(): array
    {
        return [
            'EUR' => 100000,
            'CHF' => 110000,
        ];
    }
}
