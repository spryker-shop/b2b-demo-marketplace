<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ProductConfiguratorGatewayPage;

use SprykerShop\Yves\ProductConfiguratorGatewayPage\ProductConfiguratorGatewayPageConfig as SprykerProductConfiguratorGatewayPageConfig;

class ProductConfiguratorGatewayPageConfig extends SprykerProductConfiguratorGatewayPageConfig
{
    /**
     * @var string
     *
     * @uses \Pyz\Shared\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig::WATER_TREATMENT_CONFIGURATOR_KEY
     */
    protected const WATER_TREATMENT_CONFIGURATOR_KEY = 'WATER_TREATMENT_CONFIGURATOR';

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedConfiguratorKeys(): array
    {
        return [
            static::WATER_TREATMENT_CONFIGURATOR_KEY,
        ];
    }
}
