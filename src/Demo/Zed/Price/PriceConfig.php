<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Price;

use Spryker\Zed\Price\PriceConfig as SprykerPriceConfig;

/**
 * @method \Demo\Shared\Price\PriceConfig getSharedConfig()
 */
class PriceConfig extends SprykerPriceConfig
{
    /**
     * @api
     */
    public function getCostPriceModeIdentifier(): string
    {
        return $this->getSharedConfig()->getCostPriceModeIdentifier();
    }
}
