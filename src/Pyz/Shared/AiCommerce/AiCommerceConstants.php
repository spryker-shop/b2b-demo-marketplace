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
}
