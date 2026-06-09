<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\ProductConfiguration\Plugin;

use Generated\Shared\Transfer\ProductConfiguratorRequestTransfer;
use Pyz\Shared\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ProductConfigurationExtension\Dependency\Plugin\ProductConfiguratorRequestExpanderPluginInterface;

/**
 * Routes each configurator_key to its own configurator host (access token request URL).
 */
class ConfiguratorKeyProductConfiguratorRequestExpanderPlugin extends AbstractPlugin implements ProductConfiguratorRequestExpanderPluginInterface
{
    /**
     * @var string
     */
    protected const DATE_TIME_CONFIGURATOR_KEY = 'DATE_TIME_CONFIGURATOR';

    /**
     * @param \Generated\Shared\Transfer\ProductConfiguratorRequestTransfer $productConfiguratorRequestTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConfiguratorRequestTransfer
     */
    public function expand(
        ProductConfiguratorRequestTransfer $productConfiguratorRequestTransfer
    ): ProductConfiguratorRequestTransfer {
        $configuratorKey = $productConfiguratorRequestTransfer
            ->getProductConfiguratorRequestDataOrFail()
            ->getConfiguratorKey();

        $map = $this->getConfiguratorKeyToHostMap();

        if (!$configuratorKey || !array_key_exists($configuratorKey, $map)) {
            return $productConfiguratorRequestTransfer;
        }

        return $productConfiguratorRequestTransfer
            ->setAccessTokenRequestUrl($this->createConfiguratorUrl($map[$configuratorKey]));
    }

    /**
     * @param array<string, string> $configuratorEndpoint
     *
     * @return string
     */
    protected function createConfiguratorUrl(array $configuratorEndpoint): string
    {
        return sprintf(
            '%s://%s',
            $configuratorEndpoint['port'] === '443' ? 'https' : 'http',
            $configuratorEndpoint['host'],
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getConfiguratorKeyToHostMap(): array
    {
        return [
            static::DATE_TIME_CONFIGURATOR_KEY => [
                'host' => getenv('SPRYKER_PRODUCT_CONFIGURATOR_HOST') ?: '',
                'port' => (string)(getenv('SPRYKER_PRODUCT_CONFIGURATOR_PORT') ?: ''),
            ],
            WaterTreatmentConfiguratorConfig::WATER_TREATMENT_CONFIGURATOR_KEY => [
                'host' => getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_HOST') ?: '',
                'port' => (string)(getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_PORT') ?: ''),
            ],
        ];
    }
}
