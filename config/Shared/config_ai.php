<?php

declare(strict_types = 1);

use Pyz\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_OPENAI_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_ANTHROPIC_MODEL,
        ],
    ],
];
