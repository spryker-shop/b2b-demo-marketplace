<?php

declare(strict_types = 1);

use Demo\Shared\BackofficeAssistant\BackofficeAssistantConstants;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\DiscountAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\GeneralPurposeAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\OrderAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\ProductAgentPlugin;
use Pyz\Shared\ProductManagementAi\ProductManagementAiConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => getenv('OPEN_AI_API_TOKEN') ?: '',
        'model' => 'gpt-4o-mini',
    ],
];

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_CATEGORY_SUGGESTION => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_CONTENT_IMPROVER => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_TRANSLATION => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_IMAGE_ALT_TEXT_GENERATION => $openAiConfiguration,
    BackofficeAssistantConstants::AI_CONFIGURATION_INTENT_ROUTER => array_merge($openAiConfiguration, [
        'system_prompt' => 'You are a helpful assistant that can route intents to the correct backoffice assistant. Always be brief and concise.',
    ]),
    BackofficeAssistantConstants::AI_CONFIGURATION_GENERAL_PURPOSE => array_merge($openAiConfiguration, [
        'system_prompt' => GeneralPurposeAgentPlugin::SYSTEM_PROMPT,
        'provider_config' => array_merge($openAiConfiguration['provider_config'], [
            'model' => 'gpt-4.1',
        ]),
    ]),
    BackofficeAssistantConstants::AI_CONFIGURATION_PRODUCT => array_merge($openAiConfiguration, [
        'system_prompt' => ProductAgentPlugin::SYSTEM_PROMPT,
    ]),
    BackofficeAssistantConstants::AI_CONFIGURATION_DISCOUNT => array_merge($openAiConfiguration, [
        'system_prompt' => DiscountAgentPlugin::SYSTEM_PROMPT,
    ]),
    BackofficeAssistantConstants::AI_CONFIGURATION_ORDER => array_merge($openAiConfiguration, [
        'system_prompt' => OrderAgentPlugin::SYSTEM_PROMPT,
    ]),
];
