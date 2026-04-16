<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Pyz\Yves\AiCommerce\AiCommerceConfig as AiCommerceConfigAlias;

class AiCommerceConfig extends AiCommerceConfigAlias
{
    public function getQuickOrderImageToCartAiConfigurationName(): ?string
    {
        return AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI;
    }
}
