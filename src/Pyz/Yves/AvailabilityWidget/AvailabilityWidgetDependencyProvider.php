<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\AvailabilityWidget;

use SprykerShop\Yves\AvailabilityWidget\AvailabilityWidgetDependencyProvider as SprykerShopAvailabilityWidgetDependencyProvider;
use SprykerShop\Yves\ProductMeasurementUnitWidget\Plugin\AvailabilityWidget\ProductMeasurementUnitQuantityFormatterStrategyPlugin;

class AvailabilityWidgetDependencyProvider extends SprykerShopAvailabilityWidgetDependencyProvider
{
    /**
     * @return array<\SprykerShop\Yves\AvailabilityWidgetExtension\Dependency\Plugin\AvailabilityQuantityFormatterStrategyPluginInterface>
     */
    protected function getAvailabilityQuantityFormatterStrategyPlugins(): array
    {
        return [
            new ProductMeasurementUnitQuantityFormatterStrategyPlugin(),
        ];
    }
}
