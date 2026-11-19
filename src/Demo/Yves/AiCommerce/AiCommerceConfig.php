<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Pyz\Yves\AiCommerce\AiCommerceConfig as PyzAiCommerceConfig;

class AiCommerceConfig extends PyzAiCommerceConfig
{
    /**
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantCustomPageContextFields(): array
    {
        return [
            AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_TYPE_ASSET => [
                AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_CONTEXT_FIELD_ASSET_REFERENCE,
                AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_CONTEXT_FIELD_ASSET_NAME,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getSuggestedPromptConfigurationKeysByGroup(): array
    {
        return parent::getSuggestedPromptConfigurationKeysByGroup() + [
            AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_TYPE_ASSET => AiCommerceConstants::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_ASSET_PAGE_PROMPTS,
        ];
    }
}
