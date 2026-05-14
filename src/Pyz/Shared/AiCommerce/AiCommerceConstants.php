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
     * Configuration key for the Quick Order Image-to-Cart AI configuration name.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_AI_CONFIGURATION = 'ai_commerce:quick_order:ai_vendor:ai_configuration';

    /**
     * Configuration key for the model used by the Quick Order Image-to-Cart (OpenAI) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_OPENAI_MODEL = 'ai_commerce:quick_order:ai_vendor:openai_model';

    /**
     * Configuration key for the model used by the Quick Order Image-to-Cart (AWS Bedrock) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_AWS_MODEL = 'ai_commerce:quick_order:ai_vendor:aws_model';

    /**
     * Configuration key for the model used by the Quick Order Image-to-Cart (Anthropic) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_QUICK_ORDER_IMAGE_TO_CART_ANTHROPIC_MODEL = 'ai_commerce:quick_order:ai_vendor:anthropic_model';

    /**
     * Configuration key for the Search by Image AI configuration name.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_AI_CONFIGURATION = 'ai_commerce:search_by_image:ai_vendor:ai_configuration';

    /**
     * Configuration key for the model used by the Search by Image (OpenAI) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_OPENAI_MODEL = 'ai_commerce:search_by_image:ai_vendor:openai_model';

    /**
     * Configuration key for the model used by the Search by Image (AWS Bedrock) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_AWS_MODEL = 'ai_commerce:search_by_image:ai_vendor:aws_model';

    /**
     * Configuration key for the model used by the Search by Image (Anthropic) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_SEARCH_BY_IMAGE_ANTHROPIC_MODEL = 'ai_commerce:search_by_image:ai_vendor:anthropic_model';

    /**
     * Configuration key for the Backoffice Assistant AI configuration name (shared by Intent Router, General, Order Management, Discount Management, Form Fill agents).
     *
     * @api
     */
    public const string CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AI_CONFIGURATION = 'ai_commerce:backoffice_assistant:ai_vendor:ai_configuration';

    /**
     * Configuration key for the model used by the Backoffice Assistant (OpenAI) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_OPENAI_MODEL = 'ai_commerce:backoffice_assistant:ai_vendor:openai_model';

    /**
     * Configuration key for the model used by the Backoffice Assistant (AWS Bedrock) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_AWS_MODEL = 'ai_commerce:backoffice_assistant:ai_vendor:aws_model';

    /**
     * Configuration key for the model used by the Backoffice Assistant (Anthropic) AI configuration.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_BACKOFFICE_ASSISTANT_ANTHROPIC_MODEL = 'ai_commerce:backoffice_assistant:ai_vendor:anthropic_model';

    /**
     * AI configuration name used by the Search by Image feature backed by OpenAI.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI';

    /**
     * AI configuration name used by the Search by Image feature backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SEARCH_BY_IMAGE_AWS = 'AI_COMMERCE:AI_CONFIGURATION_SEARCH_BY_IMAGE_AWS';

    /**
     * AI configuration name used by the Search by Image feature backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SEARCH_BY_IMAGE_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_SEARCH_BY_IMAGE_ANTHROPIC';

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

    /**
     * AI configuration name used by the Quick Order Image-to-Cart feature backed by OpenAI.
     *
     * @api
     */
    public const string AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI';

    /**
     * AI configuration name used by the Quick Order Image-to-Cart feature backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_AWS = 'AI_COMMERCE:AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_AWS';

    /**
     * AI configuration name used by the Quick Order Image-to-Cart feature backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_ANTHROPIC';

    /**
     * AI configuration name used by the Backoffice Assistant agents backed by OpenAI.
     *
     * @api
     */
    public const string AI_CONFIGURATION_BACKOFFICE_ASSISTANT_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_BACKOFFICE_ASSISTANT_OPENAI';

    /**
     * AI configuration name used by the Backoffice Assistant agents backed by AWS Bedrock.
     *
     * @api
     */
    public const string AI_CONFIGURATION_BACKOFFICE_ASSISTANT_AWS = 'AI_COMMERCE:AI_CONFIGURATION_BACKOFFICE_ASSISTANT_AWS';

    /**
     * AI configuration name used by the Backoffice Assistant agents backed by Anthropic.
     *
     * @api
     */
    public const string AI_CONFIGURATION_BACKOFFICE_ASSISTANT_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_BACKOFFICE_ASSISTANT_ANTHROPIC';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_INTENT_ROUTER_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_INTENT_ROUTER_OPENAI';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_INTENT_ROUTER_AWS = 'AI_COMMERCE:AI_CONFIGURATION_INTENT_ROUTER_AWS';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_INTENT_ROUTER_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_INTENT_ROUTER_ANTHROPIC';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_GENERAL_AGENT_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_GENERAL_AGENT_OPENAI';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_GENERAL_AGENT_AWS = 'AI_COMMERCE:AI_CONFIGURATION_GENERAL_AGENT_AWS';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_GENERAL_AGENT_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_GENERAL_AGENT_ANTHROPIC';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_ORDER_MANAGEMENT_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_ORDER_MANAGEMENT_OPENAI';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_ORDER_MANAGEMENT_AWS = 'AI_COMMERCE:AI_CONFIGURATION_ORDER_MANAGEMENT_AWS';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_ORDER_MANAGEMENT_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_ORDER_MANAGEMENT_ANTHROPIC';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_DISCOUNT_MANAGEMENT_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_DISCOUNT_MANAGEMENT_OPENAI';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_DISCOUNT_MANAGEMENT_AWS = 'AI_COMMERCE:AI_CONFIGURATION_DISCOUNT_MANAGEMENT_AWS';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_DISCOUNT_MANAGEMENT_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_DISCOUNT_MANAGEMENT_ANTHROPIC';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_FORM_FILL_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_FORM_FILL_OPENAI';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_FORM_FILL_AWS = 'AI_COMMERCE:AI_CONFIGURATION_FORM_FILL_AWS';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_FORM_FILL_ANTHROPIC = 'AI_COMMERCE:AI_CONFIGURATION_FORM_FILL_ANTHROPIC';
}
