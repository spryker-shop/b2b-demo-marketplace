<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\AiCommerce;

use Pyz\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig as SprykerFeatureAiCommerceConfig;

class AiCommerceConfig extends SprykerFeatureAiCommerceConfig
{
    public function getSearchByImageAiConfigurationName(): ?string
    {
        return $this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_AI_CONFIGURATION,
            AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI,
        );
    }
}
