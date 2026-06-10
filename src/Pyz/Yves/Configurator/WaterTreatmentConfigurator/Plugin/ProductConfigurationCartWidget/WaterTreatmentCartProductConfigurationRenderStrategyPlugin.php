<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin\ProductConfigurationCartWidget;

use Generated\Shared\Transfer\ProductConfigurationInstanceTransfer;
use Generated\Shared\Transfer\ProductConfigurationTemplateTransfer;
use Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin\WaterTreatmentRenderTemplateTrait;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\ProductConfigurationCartWidgetExtension\Dependency\Plugin\CartProductConfigurationRenderStrategyPluginInterface;

class WaterTreatmentCartProductConfigurationRenderStrategyPlugin extends AbstractPlugin implements CartProductConfigurationRenderStrategyPluginInterface
{
    use WaterTreatmentRenderTemplateTrait;

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
        return $this->isWaterTreatmentConfigurator($productConfigurationInstance->getConfiguratorKey());
    }

    /**
     * {@inheritDoc}
     * - Decodes the flat display data ({label: value}) for the reused `options-list` view template.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConfigurationInstanceTransfer $productConfigurationInstance
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationTemplateTransfer
     */
    public function getTemplate(ProductConfigurationInstanceTransfer $productConfigurationInstance): ProductConfigurationTemplateTransfer
    {
        return $this->createWaterTreatmentTemplate($productConfigurationInstance->getDisplayDataOrFail());
    }
}
