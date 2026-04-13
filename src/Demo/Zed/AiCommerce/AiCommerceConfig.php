<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use SprykerFeature\Zed\AiCommerce\AiCommerceConfig as SprykerFeatureAiCommerceConfig;

class AiCommerceConfig extends SprykerFeatureAiCommerceConfig
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
     * {@inheritDoc}
     *
     * @return array<string>
     */
    public function getBackofficeAssistantSseAiConfigurationNames(): array
    {
        return array_merge(parent::getBackofficeAssistantSseAiConfigurationNames(), [
            AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER,
        ]);
    }
}
