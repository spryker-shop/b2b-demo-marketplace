<?php

declare(strict_types = 1);

use Pyz\Shared\ProductManagementAi\ProductManagementAiConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => getenv('OPENAI_API_KEY') ?: null,
        'model' => 'gpt-4o',
    ],
];

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_CATEGORY_SUGGESTION => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_CONTENT_IMPROVER => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_TRANSLATION => $openAiConfiguration,
    ProductManagementAiConstants::AI_CONFIGURATION_IMAGE_ALT_TEXT_GENERATION => $openAiConfiguration,
];
