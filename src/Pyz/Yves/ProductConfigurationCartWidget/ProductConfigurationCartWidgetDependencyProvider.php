<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ProductConfigurationCartWidget;

use Pyz\Yves\WaterTreatmentConfiguratorPageExample\Plugin\ProductConfigurationCartWidget\ExampleWaterTreatmentCartProductConfigurationRenderStrategyPlugin;
use SprykerShop\Yves\ProductConfigurationCartWidget\ProductConfigurationCartWidgetDependencyProvider as SprykerProductConfigurationCartWidgetDependencyProvider;

class ProductConfigurationCartWidgetDependencyProvider extends SprykerProductConfigurationCartWidgetDependencyProvider
{
    /**
     * @return array<\SprykerShop\Yves\ProductConfigurationCartWidgetExtension\Dependency\Plugin\CartProductConfigurationRenderStrategyPluginInterface>
     */
    protected function getCartProductConfigurationRenderStrategyPlugins(): array
    {
        return [
            new ExampleWaterTreatmentCartProductConfigurationRenderStrategyPlugin(),
        ];
    }
}
