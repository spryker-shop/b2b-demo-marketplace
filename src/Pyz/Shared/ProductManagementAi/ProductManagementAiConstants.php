<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\ProductManagementAi;

interface ProductManagementAiConstants
{
    /**
     * @api
     */
    public const string AI_CONFIGURATION_CATEGORY_SUGGESTION = 'CATEGORY_SUGGESTION';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_TRANSLATION = 'TRANSLATION';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_IMAGE_ALT_TEXT_GENERATION = 'IMAGE_ALT_TEXT_GENERATION';

    /**
     * @api
     */
    public const string AI_CONFIGURATION_CONTENT_IMPROVER = 'CONTENT_IMPROVER';
}
