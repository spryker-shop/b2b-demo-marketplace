<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\WaterTreatmentConfiguratorPageExample\Plugin\ProductConfiguration;

use Generated\Shared\Transfer\ProductConfiguratorRequestTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ProductConfigurationExtension\Dependency\Plugin\ProductConfiguratorRequestExpanderPluginInterface;

class ExampleWaterTreatmentProductConfiguratorRequestExpanderPlugin extends AbstractPlugin implements ProductConfiguratorRequestExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Sets the Water Treatment configurator host as the access token request URL.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConfiguratorRequestTransfer $productConfiguratorRequestTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConfiguratorRequestTransfer
     */
    public function expand(
        ProductConfiguratorRequestTransfer $productConfiguratorRequestTransfer,
    ): ProductConfiguratorRequestTransfer {
        return $productConfiguratorRequestTransfer->setAccessTokenRequestUrl($this->createConfiguratorUrl());
    }

    /**
     * @return string
     */
    protected function createConfiguratorUrl(): string
    {
        return sprintf(
            '%s://%s',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_PORT') === '443' ? 'https' : 'http',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_HOST') ?: '',
        );
    }
}
