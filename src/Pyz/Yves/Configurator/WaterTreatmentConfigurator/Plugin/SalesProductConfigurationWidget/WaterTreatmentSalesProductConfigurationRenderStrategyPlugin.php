<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin\SalesProductConfigurationWidget;

use Generated\Shared\Transfer\ProductConfigurationTemplateTransfer;
use Generated\Shared\Transfer\SalesOrderItemConfigurationTransfer;
use Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin\WaterTreatmentRenderTemplateTrait;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\SalesProductConfigurationWidgetExtension\Dependency\Plugin\SalesProductConfigurationRenderStrategyPluginInterface;

class WaterTreatmentSalesProductConfigurationRenderStrategyPlugin extends AbstractPlugin implements SalesProductConfigurationRenderStrategyPluginInterface
{
    use WaterTreatmentRenderTemplateTrait;

    /**
     * {@inheritDoc}
     * - Applicable to order items configured with the Water Treatment configurator.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SalesOrderItemConfigurationTransfer $salesOrderItemConfigurationTransfer
     *
     * @return bool
     */
    public function isApplicable(SalesOrderItemConfigurationTransfer $salesOrderItemConfigurationTransfer): bool
    {
        return $this->isWaterTreatmentConfigurator($salesOrderItemConfigurationTransfer->getConfiguratorKey());
    }

    /**
     * {@inheritDoc}
     * - Decodes the flat display data ({label: value}) for the reused `options-list` view template.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SalesOrderItemConfigurationTransfer $salesOrderItemConfigurationTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationTemplateTransfer
     */
    public function getTemplate(SalesOrderItemConfigurationTransfer $salesOrderItemConfigurationTransfer): ProductConfigurationTemplateTransfer
    {
        return $this->createWaterTreatmentTemplate($salesOrderItemConfigurationTransfer->getDisplayDataOrFail());
    }
}
