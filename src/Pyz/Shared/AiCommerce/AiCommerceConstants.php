<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\AiCommerce;

use SprykerFeature\Shared\AiCommerce\AiCommerceConstants as SprykerFeatureAiCommerceConstants;

interface AiCommerceConstants extends SprykerFeatureAiCommerceConstants
{
    /**
     * Configuration key for the OpenAI API token.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_OPENAI_API_TOKEN = 'ai_vendor:openai:general:api_token';

    /**
     * Configuration key for the AWS Bedrock API token.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_AWS_API_TOKEN = 'ai_vendor:aws:general:api_token';

    /**
     * Configuration key for the AWS region used for all AWS Bedrock requests.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_AWS_REGION = 'ai_vendor:aws:general:region';

    /**
     * Configuration key for the Anthropic API token.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_ANTHROPIC_API_TOKEN = 'ai_vendor:anthropic:general:api_token';

    /**
     * Configuration key for the Smart PIM AI configuration name.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_AI_CONFIGURATION = 'ai_commerce:smart_pim:ai_vendor:ai_configuration';

    /**
     * Configuration key for the model used by the Smart PIM (OpenAI) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_OPENAI_MODEL = 'ai_commerce:smart_pim:ai_vendor:openai_model';

    /**
     * Configuration key for the model used by the Smart PIM (AWS Bedrock) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_AWS_MODEL = 'ai_commerce:smart_pim:ai_vendor:aws_model';

    /**
     * Configuration key for the model used by the Smart PIM (Anthropic) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_PIM_ANTHROPIC_MODEL = 'ai_commerce:smart_pim:ai_vendor:anthropic_model';

    /**
     * AI configuration name used by the Smart PIM agent for handling PIM-related queries and actions.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_PIM_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_SMART_PIM_OPENAI';

    /**
     * AI configuration name used by the Smart PIM agent backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_PIM_AWS = 'AI_COMMERCE:AI_CONFIGURATION_SMART_PIM_AWS';

    /**
     * AI configuration name used by the Smart PIM agent backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_PIM_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_SMART_PIM_ANTHROPIC';
}
