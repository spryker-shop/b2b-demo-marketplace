<?php

declare(strict_types = 1);

use Demo\Shared\AiCommerce\AiCommerceConstants;

/**
 * Here go your local configs which should not be version controlled (tokens, passwords, keys, ...)
 */

/*
 * AWS credentials for the Amazon Bedrock knowledge base store (knowledge documents of the Storefront Assistant).
 * The knowledge base APIs accept no Bedrock API key. Get temporary credentials with `aws configure export-credentials --profile <profile>`.
 * Use them only locally: cloud environments use an IAM role.
 */
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_ACCESS_KEY_ID] = '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SECRET_ACCESS_KEY] = '';
$config[AiCommerceConstants::BEDROCK_KNOWLEDGE_BASE_AWS_SESSION_TOKEN] = '';
