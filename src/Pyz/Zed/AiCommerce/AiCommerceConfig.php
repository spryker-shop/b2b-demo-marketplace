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

    /**
     * Specification:
     * - Returns the AI configuration name used by all Backoffice Assistant agents (Intent Router, General, Order Management, Discount Management, Form Fill).
     *
     * @api
     */
    public function getBackofficeAssistantAiConfigurationName(): ?string
    {
        return (string)$this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AI_CONFIGURATION,
            AiCommerceConstants::AI_CONFIGURATION_BACKOFFICE_ASSISTANT_OPENAI,
        );
    }

    /**
     * @api
     */
    public function getIntentRouterAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_AWS,
            AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_ANTHROPIC,
        );
    }

    /**
     * @api
     */
    public function getGeneralAgentAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_AWS,
            AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_ANTHROPIC,
        );
    }

    /**
     * @api
     */
    public function getOrderManagementAgentAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_AWS,
            AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_ANTHROPIC,
        );
    }

    /**
     * @api
     */
    public function getDiscountManagementAgentAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_AWS,
            AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_ANTHROPIC,
        );
    }

    /**
     * @api
     */
    public function getFormFillAgentAiConfigurationName(): ?string
    {
        return $this->resolveBackofficeAssistantAgentAiConfigurationName(
            AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_OPENAI,
            AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_AWS,
            AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_ANTHROPIC,
        );
    }

    protected function resolveBackofficeAssistantAgentAiConfigurationName(
        string $openAiName,
        string $awsName,
        string $anthropicName,
    ): string {
        return match ($this->getBackofficeAssistantAiConfigurationName()) {
            AiCommerceConstants::AI_CONFIGURATION_BACKOFFICE_ASSISTANT_AWS => $awsName,
            AiCommerceConstants::AI_CONFIGURATION_BACKOFFICE_ASSISTANT_ANTHROPIC => $anthropicName,
            default => $openAiName,
        };
    }

    protected function getSmartPimAiConfigurationName(): ?string
    {
        return $this->getModuleConfig(
            AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_AI_CONFIGURATION,
            AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI,
        );
    }
}
