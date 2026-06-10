<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Shared\AiCommerce;

use Pyz\Shared\AiCommerce\AiCommerceConstants as PyzFeatureAiCommerceConstants;

interface AiCommerceConstants extends PyzFeatureAiCommerceConstants
{
    /**
     * Tool set name that groups all place order tools available to the place order agent.
     *
     * @api
     */
    public const string TOOL_SET_PLACE_ORDER = 'place_order_tools';

    /**
     * Tool set name that groups order detail reading tools.
     *
     * @api
     */
    public const string TOOL_SET_ORDER_DETAILS = 'order_details_tools';

    /**
     * Configuration key for the place order agent system prompt.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_PLACE_ORDER_SYSTEM_PROMPT = 'ai_commerce:backoffice_assistant:system_prompts:place_order_system_prompt';

    /**
     * Configuration key for the place order agent enabled flag.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_PLACE_ORDER_AGENT_IS_ENABLED = 'ai_commerce:backoffice_assistant:general:is_place_order_agent_enabled';

    /**
     * AI configuration name used by the place order agent backed by OpenAI.
     *
     * @api
     */
    public const string AI_CONFIGURATION_PLACE_ORDER_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_PLACE_ORDER_OPENAI';

    /**
     * AI configuration name used by the place order agent backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_PLACE_ORDER_AWS = 'AI_COMMERCE:AI_CONFIGURATION_PLACE_ORDER_AWS';

    /**
     * AI configuration name used by the place order agent backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_PLACE_ORDER_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_PLACE_ORDER_ANTHROPIC';

    /**
     * Configuration key for the Smart CMS AI configuration name.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_AI_CONFIGURATION = 'ai_commerce:smart_cms:ai_vendor:ai_configuration';

    /**
     * Configuration key for the model used by the Smart CMS (OpenAI) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_OPENAI_MODEL = 'ai_commerce:smart_cms:ai_vendor:openai_model';

    /**
     * Configuration key for the model used by the Smart CMS (AWS Bedrock) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_AWS_MODEL = 'ai_commerce:smart_cms:ai_vendor:aws_model';

    /**
     * Configuration key for the model used by the Smart CMS (Anthropic) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SMART_CMS_ANTHROPIC_MODEL = 'ai_commerce:smart_cms:ai_vendor:anthropic_model';

    /**
     * AI configuration name used by the Smart CMS feature backed by OpenAI.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_CMS_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_SMART_CMS_OPENAI';

    /**
     * AI configuration name used by the Smart CMS feature backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_CMS_AWS = 'AI_COMMERCE:AI_CONFIGURATION_SMART_CMS_AWS';

    /**
     * AI configuration name used by the Smart CMS feature backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SMART_CMS_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_SMART_CMS_ANTHROPIC';
}
