<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\AvailabilityWidget;

use SprykerShop\Yves\AvailabilityWidget\AvailabilityWidgetConfig as SprykerShopAvailabilityWidgetConfig;

class AvailabilityWidgetConfig extends SprykerShopAvailabilityWidgetConfig
{
    protected const bool STOCK_DISPLAY_ENABLED = true;

    public function getStockDisplayMode(): string
    {
        return static::STOCK_DISPLAY_MODE_INDICATOR_AND_QUANTITY;
    }
}
