<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductManagementAi;

use Pyz\Shared\ProductManagementAi\ProductManagementAiConstants;
use SprykerEco\Zed\ProductManagementAi\ProductManagementAiConfig as SprykerProductManagementAiConfig;

class ProductManagementAiConfig extends SprykerProductManagementAiConfig
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string|null
     */
    public function getCategorySuggestionAiConfigurationName(): ?string
    {
        return ProductManagementAiConstants::AI_CONFIGURATION_CATEGORY_SUGGESTION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string|null
     */
    public function getTranslationAiConfigurationName(): ?string
    {
        return ProductManagementAiConstants::AI_CONFIGURATION_TRANSLATION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string|null
     */
    public function getImageAltTextAiConfigurationName(): ?string
    {
        return ProductManagementAiConstants::AI_CONFIGURATION_IMAGE_ALT_TEXT_GENERATION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string|null
     */
    public function getContentImproverAiConfigurationName(): ?string
    {
        return ProductManagementAiConstants::AI_CONFIGURATION_CONTENT_IMPROVER;
    }
}
