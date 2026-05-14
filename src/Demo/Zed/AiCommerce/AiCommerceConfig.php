<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Pyz\Zed\AiCommerce\AiCommerceConfig as PyzAiCommerceConfig;

class AiCommerceConfig extends PyzAiCommerceConfig
{
    /**
     * Specification:
     * - Returns true if the Place Order Agent is enabled.
     *
     * @api
     */
    public function isPlaceOrderAgentEnabled(): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig(AiCommerceConstants::CONFIGURATION_KEY_PLACE_ORDER_AGENT_IS_ENABLED, true),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Specification:
     * - Returns the AI configuration name used by the Place Order agent, resolved via the Backoffice Assistant vendor radio.
     *
     * @api
     */
    public function getPlaceOrderAgentAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_AWS,
            AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_ANTHROPIC,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string>
     */
    public function getBackofficeAssistantSseAiConfigurationNames(): array
    {
        return array_merge(parent::getBackofficeAssistantSseAiConfigurationNames(), [
            $this->getPlaceOrderAgentAiConfigurationName(),
        ]);
    }
}
