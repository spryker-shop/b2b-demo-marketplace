<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\AiCommerce;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Pyz\Client\AiCommerce\AiCommerceConfig as PyzAiCommerceConfig;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CatalogSuggestToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CategoryTreeToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\CompareProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplayProductsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\DisplaySetupToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\OfferChoicesToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductDetailsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductRelationsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductSetsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ReadShopPageToolPlugin;

class AiCommerceConfig extends PyzAiCommerceConfig
{
    public function getStorefrontAssistantAiConfigurationName(): string
    {
        return (string)$this->getModuleConfig(AiCommerceConstants::CONFIGURATION_KEY_STOREFRONT_ASSISTANT_AI_CONFIGURATION);
    }

    /**
     * @return list<string>
     */
    public function getProductDiscoveryAgentToolNames(): array
    {
        return [
            CatalogSearchToolPlugin::TOOL_NAME,
            CatalogSuggestToolPlugin::TOOL_NAME,
            CategoryTreeToolPlugin::TOOL_NAME,
            DisplayProductsToolPlugin::TOOL_NAME,
            ProductDetailsToolPlugin::TOOL_NAME,
            ProductRelationsToolPlugin::TOOL_NAME,
            ReadShopPageToolPlugin::TOOL_NAME,
            CompareProductsToolPlugin::TOOL_NAME,
            ProductSetsToolPlugin::TOOL_NAME,
            OfferChoicesToolPlugin::TOOL_NAME,
            DisplaySetupToolPlugin::TOOL_NAME,
        ];
    }

    /**
     * @return array<string>
     */
    public function getStorefrontAssistantCustomerFacingToolNames(): array
    {
        return [
            DisplayProductsToolPlugin::TOOL_NAME,
            CompareProductsToolPlugin::TOOL_NAME,
            OfferChoicesToolPlugin::TOOL_NAME,
            DisplaySetupToolPlugin::TOOL_NAME,
        ];
    }
}
