<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiCommerceConstants::AI_CONFIGURATION_SMART_PIM_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_PIM_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'minimal',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'minimal',
                ],
            ],
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
    AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'medium',
                    'summary' => 'auto',
                ],
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
            'inferenceConfig' => [
                'maxTokens' => 8192,
            ],
            'additionalModelRequestFields' => [
                'thinking' => [
                    'type' => 'enabled',
                    'budget_tokens' => 2048,
                ],
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_STOREFRONT_ASSISTANT_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_ANTHROPIC_MODEL,
            'max_tokens' => 8192,
            'parameters' => [
                'thinking' => [
                    'type' => 'enabled',
                    'budget_tokens' => 2048,
                ],
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SEARCH_BY_IMAGE_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'minimal',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'none',
                ],
            ],
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
    AiCommerceConstants::AI_CONFIGURATION_SMART_CMS_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'minimal',
                ],
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_SMART_CMS_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
            'inferenceConfig' => [
                'maxTokens' => 16384,
            ],
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_SYSTEM_PROMPT,
    ],
    AiCommerceConstants::AI_CONFIGURATION_SMART_CMS_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_ANTHROPIC_MODEL,
        ],
        'system_prompt' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_SMART_CMS_SYSTEM_PROMPT,
    ],
];
