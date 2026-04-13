<?php

declare(strict_types = 1);

use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Shared\AiCommerce\AiCommerceConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => getenv('OPEN_AI_API_TOKEN') ?: '',
        'model' => 'gpt-4o',
    ],
];

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER => $openAiConfiguration,
    AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT,
        'provider_config' => array_merge($openAiConfiguration['provider_config'], [
            'model' => 'gpt-4.1', // fast non-reasoning model
        ]),
    ]),
    AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT,
        'provider_config' => array_merge($openAiConfiguration['provider_config'], [
            'model' => 'gpt-4.1', // fast non-reasoning model
        ]),
    ]),
    AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT,
        'provider_config' => array_merge($openAiConfiguration['provider_config'], [
            'model' => 'gpt-4.1', // fast non-reasoning model
        ]),
    ]),
    AiCommerceConstants::AI_CONFIGURATION_FORM_FILL => array_merge($openAiConfiguration, [
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT,
        'provider_config' => array_merge($openAiConfiguration['provider_config'], [
            'model' => 'gpt-4.1', // fast non-reasoning model
        ]),
    ]),
];
