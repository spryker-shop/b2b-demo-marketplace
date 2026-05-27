<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\PriceProduct;

use Demo\Shared\Price\PriceConfig;
use Spryker\Client\PriceProduct\PriceProductConfig as SprykerPriceProductConfig;

class PriceProductConfig extends SprykerPriceProductConfig
{
    public function getPriceModeIdentifierForCostType(): string
    {
        return PriceConfig::PRICE_MODE_COST;
    }
}
