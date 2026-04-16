<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Shared\AiCommerce;

use SprykerFeature\Shared\AiCommerce\AiCommerceConstants as SprykerFeatureAiCommerceConstants;

interface AiCommerceConstants extends SprykerFeatureAiCommerceConstants
{
    /**
     * Configuration key for the OpenAI API token.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_OPENAI_API_TOKEN = 'ai_commerce:open_ai:general:openai_api_token';

    /**
     * Configuration key for the default OpenAI model used for general AI operations.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_OPENAI_DEFAULT_MODEL = 'ai_commerce:open_ai:general:openai_default_model';

    /**
     * Configuration key for the fast non-reasoning OpenAI model used for agent operations.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_OPENAI_SMART_MODEL = 'ai_commerce:open_ai:general:openai_smart_model';

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
     * AI configuration name used by the place order agent for handling order placement queries and actions.
     *
     * @api
     */
    public const string AI_CONFIGURATION_PLACE_ORDER = 'AI_CONFIGURATION_PLACE_ORDER';

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
     * AI configuration name via OpenAI used by the search-by-image feature.
     *
     * @api
     */
    public const string AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_SEARCH_BY_IMAGE_OPENAI';

    /**
     * AI configuration name via OpenAI used by the quick order image-to-cart feature.
     *
     * @api
     */
    public const string AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI = 'AI_COMMERCE:AI_CONFIGURATION_QUICK_ORDER_IMAGE_TO_CART_OPENAI';
}
