<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
        'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_DEFAULT_MODEL,
    ],
];

$openAiSmartAgentModelConfig = array_merge($openAiConfiguration['provider_config'], [
    'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_SMART_MODEL,
]);

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI => $openAiConfiguration,
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
    AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_PLACE_ORDER_SYSTEM_PROMPT,
        'provider_config' => $openAiSmartAgentModelConfig,
    ]),
];
