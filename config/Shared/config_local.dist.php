<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Shared\AiFoundation\AiFoundationConstants;

/**
 * Here go your local configs which should not be version controlled (tokens, passwords, keys, ...)
 */

$openAiToken = '';
$bedrockToken = '';
$bedrockRegion = 'eu-central-1';

if (interface_exists(AiFoundationConstants::class)) {
    foreach ($config[AiFoundationConstants::AI_CONFIGURATIONS] ?? [] as $configName => $aiConfiguration) {
        $providerName = $aiConfiguration[AiFoundationConstants::AI_PROVIDER_NAME] ?? null;

        if ($openAiToken && in_array($providerName, [AiFoundationConstants::PROVIDER_OPENAI, AiFoundationConstants::PROVIDER_OPENAI_RESPONSES], true)) {
            $config[AiFoundationConstants::AI_CONFIGURATIONS][$configName]['provider_config']['key'] = $openAiToken;
        }

        if (!$bedrockToken || $providerName !== AiFoundationConstants::PROVIDER_BEDROCK) {
            continue;
        }

        $config[AiFoundationConstants::AI_CONFIGURATIONS][$configName]['provider_config']['bedrockRuntimeClient']['token'] = $bedrockToken;
        $config[AiFoundationConstants::AI_CONFIGURATIONS][$configName]['provider_config']['bedrockRuntimeClient']['region'] = $bedrockRegion;
    }
}

/*
 * AWS credentials for the Amazon Bedrock knowledge base store (knowledge documents of the Storefront Assistant).
 * The knowledge base APIs accept no Bedrock API key. Get temporary credentials with `aws configure export-credentials --profile <profile>`.
 * Use them only locally: cloud environments use an IAM role.
 */
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID] = '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY] = '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN] = '';
