<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Communication\Plugin\SalesProductConfigurationGui;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\SalesProductConfigurationTemplateTransfer;
use Pyz\Shared\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesProductConfigurationGuiExtension\Dependency\Plugin\ProductConfigurationRenderStrategyPluginInterface;

/**
 * @method \Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\WaterTreatmentConfiguratorPageExampleFacadeInterface getFacade()
 * @method \Pyz\Zed\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig getConfig()
 */
class ExampleWaterTreatmentProductConfigurationRenderStrategyPlugin extends AbstractPlugin implements ProductConfigurationRenderStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     * - Applicable to order items configured with the Water Treatment configurator.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return bool
     */
    public function isApplicable(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getSalesOrderItemConfiguration()
            && $itemTransfer->getSalesOrderItemConfigurationOrFail()->getConfiguratorKey()
                === WaterTreatmentConfiguratorPageExampleConfig::WATER_TREATMENT_CONFIGURATOR_KEY;
    }

    /**
     * {@inheritDoc}
     * - Decodes the flat display data ({label: value}) and renders it via the reused
     *   order-item configuration partial.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Generated\Shared\Transfer\SalesProductConfigurationTemplateTransfer
     */
    public function getTemplate(ItemTransfer $itemTransfer): SalesProductConfigurationTemplateTransfer
    {
        return (new SalesProductConfigurationTemplateTransfer())
            ->setData(json_decode($itemTransfer->getSalesOrderItemConfigurationOrFail()->getDisplayDataOrFail(), true) ?? [])
            ->setTemplatePath('@WaterTreatmentConfiguratorPageExample/_partials/order-item-configuration.twig');
    }
}
