<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ProductConfigurationShoppingListWidget;

use SprykerShop\Yves\ProductConfigurationShoppingListWidget\ProductConfigurationShoppingListWidgetConfig as SprykerProductConfigurationShoppingListWidgetConfig;

class ProductConfigurationShoppingListWidgetConfig extends SprykerProductConfigurationShoppingListWidgetConfig
{
    /**
     * @var string
     *
     * @uses \Pyz\Shared\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig::WATER_TREATMENT_CONFIGURATOR_KEY
     */
    protected const WATER_TREATMENT_CONFIGURATOR_KEY = 'WATER_TREATMENT_CONFIGURATOR';

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedConfiguratorKeys(): array
    {
        return array_merge(parent::getSupportedConfiguratorKeys(), [
            static::WATER_TREATMENT_CONFIGURATOR_KEY,
        ]);
    }
}
