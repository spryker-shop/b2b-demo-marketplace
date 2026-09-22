<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SalesProductConfigurationGui;

use Pyz\Zed\WaterTreatmentConfiguratorPageExample\Communication\Plugin\SalesProductConfigurationGui\ExampleWaterTreatmentProductConfigurationRenderStrategyPlugin;
use Spryker\Zed\SalesProductConfigurationGui\SalesProductConfigurationGuiDependencyProvider as SprykerSalesProductConfigurationGuiDependencyProvider;

class SalesProductConfigurationGuiDependencyProvider extends SprykerSalesProductConfigurationGuiDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\SalesProductConfigurationGuiExtension\Dependency\Plugin\ProductConfigurationRenderStrategyPluginInterface>
     */
    protected function getProductConfigurationRenderStrategyPlugins(): array
    {
        return [
            new ExampleWaterTreatmentProductConfigurationRenderStrategyPlugin(),
        ];
    }
}
