<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;
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
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_OPENAI_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_ANTHROPIC_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_OPENAI_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_ANTHROPIC_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_INTENT_ROUTER_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_GENERAL_AGENT_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_GENERAL_PURPOSE_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_ORDER_MANAGEMENT_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ORDER_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_DISCOUNT_MANAGEMENT_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_DISCOUNT_MANAGEMENT_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_FORM_FILL_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_FORM_FILL_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_PLACE_ORDER_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_PLACE_ORDER_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_PLACE_ORDER_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_PLACE_ORDER_SYSTEM_PROMPT,
    ],
];
