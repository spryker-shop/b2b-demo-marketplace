<?php

declare(strict_types = 1);

use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . 'ai_commerce:open_ai:general:openai_api_token',
        'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . 'ai_commerce:open_ai:general:openai_default_model',
    ],
];

$openAiSmartAgentModelConfig = array_merge($openAiConfiguration['provider_config'], [
    'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . 'ai_commerce:open_ai:general:openai_smart_model',
]);

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT,
        'provider_config' => $openAiSmartAgentModelConfig,
    ]),
    AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT,
        'provider_config' => $openAiSmartAgentModelConfig,
    ]),
    AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT,
        'provider_config' => $openAiSmartAgentModelConfig,
    ]),
    AiCommerceConstants::AI_CONFIGURATION_FORM_FILL => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT,
        'provider_config' => $openAiSmartAgentModelConfig,
    ]),
];
