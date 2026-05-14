<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\AiCommerce;

use Pyz\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Yves\AiCommerce\AiCommerceConfig as SprykerAiCommerceConfig;

class AiCommerceConfig extends SprykerAiCommerceConfig
{
    public function isQuickOrderImageToCartEnabled(): bool
    {
        return true;
    }

    /**
     * Specification:
     * - Returns the AI configuration name used for image-to-cart product recognition.
     *
     * @api
     */
    public function getQuickOrderImageToCartAiConfigurationName(): ?string
    {
        return $this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_AI_CONFIGURATION,
            AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI,
        );
    }
}
