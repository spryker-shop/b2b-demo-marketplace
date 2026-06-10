<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin\ProductConfigurationWidget;

use Generated\Shared\Transfer\ProductConfigurationInstanceTransfer;
use Generated\Shared\Transfer\ProductConfigurationTemplateTransfer;
use Pyz\Shared\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\ProductConfigurationWidgetExtension\Dependency\Plugin\ProductConfigurationRenderStrategyPluginInterface;

class WaterTreatmentProductConfigurationRenderStrategyPlugin extends AbstractPlugin implements ProductConfigurationRenderStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     * - Applicable to items configured with the Water Treatment configurator.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConfigurationInstanceTransfer $productConfigurationInstance
     *
     * @return bool
     */
    public function isApplicable(ProductConfigurationInstanceTransfer $productConfigurationInstance): bool
    {
        return $productConfigurationInstance->getConfiguratorKey()
            === WaterTreatmentConfiguratorConfig::WATER_TREATMENT_CONFIGURATOR_KEY;
    }

    /**
     * {@inheritDoc}
     * - Decodes the flat display data ({label: value}); the widget wraps it into `listItems`
     *   for the reused `options-list` view template, which renders it as a list.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConfigurationInstanceTransfer $productConfigurationInstance
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationTemplateTransfer
     */
    public function getTemplate(ProductConfigurationInstanceTransfer $productConfigurationInstance): ProductConfigurationTemplateTransfer
    {
        return (new ProductConfigurationTemplateTransfer())
            ->setData(json_decode($productConfigurationInstance->getDisplayDataOrFail(), true) ?? [])
            ->setModuleName('DateTimeConfiguratorPageExample')
            ->setTemplateType('view')
            ->setTemplateName('options-list');
    }
}
