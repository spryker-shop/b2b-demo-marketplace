<?php

declare(strict_types = 1);

use Spryker\Shared\AiFoundation\AiFoundationConstants;

$openAiConfiguration = [
    'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
    'provider_config' => [
        'key' => getenv('OPEN_AI_API_TOKEN') ?: '',
        'model' => 'gpt-4o',
    ],
];

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => $openAiConfiguration,
];
