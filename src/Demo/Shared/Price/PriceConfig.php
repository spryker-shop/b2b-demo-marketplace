<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Shared\Price;

use Spryker\Shared\Price\PriceConfig as SprykerPriceConfig;

class PriceConfig extends SprykerPriceConfig
{
    /**
     * @var string
     */
    public const PRICE_MODE_COST = 'COST_MODE';

    /**
     * @api
     */
    public function getCostPriceModeIdentifier(): string
    {
        return static::PRICE_MODE_COST;
    }
}
