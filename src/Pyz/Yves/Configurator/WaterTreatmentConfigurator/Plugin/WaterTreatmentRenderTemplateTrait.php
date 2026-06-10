<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Configurator\WaterTreatmentConfigurator\Plugin;

use Generated\Shared\Transfer\ProductConfigurationTemplateTransfer;
use Pyz\Shared\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig;

/**
 * Shared logic for the Water Treatment configurator render-strategy plugins
 * (a thin plugin is still required per widget extension point).
 */
trait WaterTreatmentRenderTemplateTrait
{
    /**
     * @param string|null $configuratorKey
     *
     * @return bool
     */
    protected function isWaterTreatmentConfigurator(?string $configuratorKey): bool
    {
        return $configuratorKey === WaterTreatmentConfiguratorConfig::WATER_TREATMENT_CONFIGURATOR_KEY;
    }

    /**
     * Reuses the generic `options-list` view template; the consuming widget wraps the
     * flat display data ({label: value}) into its `listItems` structure.
     *
     * @param string $displayData
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationTemplateTransfer
     */
    protected function createWaterTreatmentTemplate(string $displayData): ProductConfigurationTemplateTransfer
    {
        return (new ProductConfigurationTemplateTransfer())
            ->setData(json_decode($displayData, true) ?? [])
            ->setModuleName('DateTimeConfiguratorPageExample')
            ->setTemplateType('view')
            ->setTemplateName('options-list');
    }
}
