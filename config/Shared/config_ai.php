<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig;

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
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_VISUAL_ADD_TO_CART_OPENAI => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI_RESPONSES,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_OPENAI_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_VISUAL_ADD_TO_CART_OPENAI_MODEL,
            'parameters' => [
                'reasoning' => [
                    'effort' => 'minimal',
                ],
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_VISUAL_ADD_TO_CART_AWS => [
        'provider_name' => AiFoundationConstants::PROVIDER_BEDROCK,
        'provider_config' => [
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_VISUAL_ADD_TO_CART_AWS_MODEL,
            'bedrockRuntimeClient' => [
                'region' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_REGION,
                'token' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_AWS_API_TOKEN,
            ],
        ],
    ],
    AiCommerceConstants::AI_CONFIGURATION_QUICK_ORDER_VISUAL_ADD_TO_CART_ANTHROPIC => [
        'provider_name' => AiFoundationConstants::PROVIDER_ANTHROPIC,
        'provider_config' => [
            'key' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_ANTHROPIC_API_TOKEN,
            'model' => AiFoundationConstants::CONFIGURATION_REFERENCE_PREFIX . AiCommerceConstants::CONFIGURATION_KEY_QUICK_ORDER_VISUAL_ADD_TO_CART_ANTHROPIC_MODEL,
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
                    'type' => 'adaptive',
                ],
                'output_config' => [
                    'effort' => 'low',
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
                    'type' => 'adaptive',
                ],
                'output_config' => [
                    'effort' => 'low',
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

$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_REGION] = getenv('SPRYKER_AI_KNOWLEDGE_BASE_AWS_REGION') ?: 'eu-central-1';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_ID] = getenv('SPRYKER_AI_KNOWLEDGE_BASE_ID') ?: 'DWYC5C7LE7';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_DATA_SOURCE_IDS] = [
    AiCommerceConfig::KNOWLEDGE_DOCUMENT_SOURCE_TYPE_PRODUCT_ATTACHMENT => getenv('SPRYKER_AI_KNOWLEDGE_BASE_DATA_SOURCE_ID') ?: 'S4ELA8B9OK',
];
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID] = getenv('AWS_ACCESS_KEY_ID') ?: '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY] = getenv('AWS_SECRET_ACCESS_KEY') ?: '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN] = getenv('AWS_SESSION_TOKEN') ?: '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_TYPE] = getenv('SPRYKER_AI_KNOWLEDGE_BASE_RERANKING_MODEL_TYPE') ?: '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_RERANKING_MODEL_ARN] = getenv('SPRYKER_AI_KNOWLEDGE_BASE_RERANKING_MODEL_ARN') ?: '';
