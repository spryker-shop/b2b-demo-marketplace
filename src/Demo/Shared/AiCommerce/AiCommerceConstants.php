<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Shared\AiCommerce;

interface AiCommerceConstants
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
}
