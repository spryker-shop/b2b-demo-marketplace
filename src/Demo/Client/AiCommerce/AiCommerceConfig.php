<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Client\AiCommerce\AiCommerceConfig as SprykerAiCommerceConfig;

class AiCommerceConfig extends SprykerAiCommerceConfig
{
    public function getSearchByImageAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI;
    }
}
