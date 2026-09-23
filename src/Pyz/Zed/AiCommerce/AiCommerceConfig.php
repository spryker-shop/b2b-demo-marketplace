<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AiCommerce;

use Pyz\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig as SprykerAiCommerceConfig;

class AiCommerceConfig extends SprykerAiCommerceConfig
{
    /**
     * Specification:
     * - Returns the AI configuration name used for the category suggestion feature.
     *
     * @api
     */
    public function getCategorySuggestionAiConfigurationName(): ?string
    {
        return $this->getSmartPimAiConfigurationName();
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the translation feature.
     *
     * @api
     */
    public function getTranslationAiConfigurationName(): ?string
    {
        return $this->getSmartPimAiConfigurationName();
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the image alt text feature.
     *
     * @api
     */
    public function getImageAltTextAiConfigurationName(): ?string
    {
        return $this->getSmartPimAiConfigurationName();
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for the content improver feature.
     *
     * @api
     */
    public function getContentImproverAiConfigurationName(): ?string
    {
        return $this->getSmartPimAiConfigurationName();
    }

    protected function getSmartPimAiConfigurationName(): ?string
    {
        return $this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_AI_CONFIGURATION,
            AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI,
        );
    }
}
