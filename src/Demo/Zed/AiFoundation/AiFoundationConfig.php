<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiFoundation;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Zed\AiFoundation\AiFoundationConfig as SprykerAiFoundationConfig;

class AiFoundationConfig extends SprykerAiFoundationConfig
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getClientResolvableAiConfigurationNames(): array
    {
        return [
            AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS,
            AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_ANTHROPIC,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getProviderPricingTabMap(): array
    {
        return [
            AiFoundationConstants::PROVIDER_OPENAI => static::PROVIDER_TAB_OPENAI,
            AiFoundationConstants::PROVIDER_OPENAI_RESPONSES => static::PROVIDER_TAB_OPENAI,
            AiFoundationConstants::PROVIDER_ANTHROPIC => static::PROVIDER_TAB_ANTHROPIC,
            AiFoundationConstants::PROVIDER_BEDROCK => static::PROVIDER_TAB_AWS,
        ];
    }
}
