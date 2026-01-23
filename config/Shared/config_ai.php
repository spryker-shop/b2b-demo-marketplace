<?php

declare(strict_types=1);

use Spryker\Shared\AiFoundation\AiFoundationConstants;

$config[AiFoundationConstants::AI_CONFIGURATIONS] = [
    AiFoundationConstants::AI_CONFIGURATION_DEFAULT => [
        'provider_name' => AiFoundationConstants::PROVIDER_OPENAI,
        'provider_config' => [
            'key' => getenv('OPENAI_API_KEY'),
            'model' => 'gpt-4o',
        ],
        'system_prompt' => '',
    ],
];
