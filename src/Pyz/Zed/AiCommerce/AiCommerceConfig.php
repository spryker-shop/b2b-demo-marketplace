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
    public function getContentImproverAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI;
    }

    public function getImageAltTextAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI;
    }

    public function getCategorySuggestionAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI;
    }

    public function getTranslationAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI;
    }
}
