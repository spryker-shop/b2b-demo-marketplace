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
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductKnowledgeSearchToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductRelationsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ProductSetsToolPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\StorefrontAssistant\Tool\ReadShopPageToolPlugin;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetCompatibleProductsToolPlugin;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetDetailsToolPlugin;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetSearchToolPlugin;

class AiCommerceConfig extends PyzAiCommerceConfig
{
    protected const string PAGE_CONTEXT_FIELD_PLACEHOLDER = '%%%s%%';

    protected const string ASSET_PAGE_PROMPT_LINE_WITH_NAME = 'The customer is viewing a page of their asset with the reference %s (shown name %s): its details page or its compatible spare parts or services. "This asset", "it" or "this machine" means this asset. Pass this reference to every tool that takes an asset reference.';

    protected const string ASSET_PAGE_PROMPT_LINE = 'The customer is viewing a page of their asset with the reference %s: its details page or its compatible spare parts or services. "This asset", "it" or "this machine" means this asset. Pass this reference to every tool that takes an asset reference.';

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
            ProductKnowledgeSearchToolPlugin::TOOL_NAME,
            SspAssetSearchToolPlugin::TOOL_NAME,
            SspAssetDetailsToolPlugin::TOOL_NAME,
            SspAssetCompatibleProductsToolPlugin::TOOL_NAME,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getStorefrontAssistantCustomPageContextPromptLines(): array
    {
        return [
            AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_TYPE_ASSET => [
                sprintf(
                    static::ASSET_PAGE_PROMPT_LINE_WITH_NAME,
                    $this->createPageContextFieldPlaceholder(AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_CONTEXT_FIELD_ASSET_REFERENCE),
                    $this->createPageContextFieldPlaceholder(AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_CONTEXT_FIELD_ASSET_NAME),
                ),
                sprintf(
                    static::ASSET_PAGE_PROMPT_LINE,
                    $this->createPageContextFieldPlaceholder(AiCommerceConstants::STOREFRONT_ASSISTANT_PAGE_CONTEXT_FIELD_ASSET_REFERENCE),
                ),
            ],
        ];
    }

    protected function createPageContextFieldPlaceholder(string $fieldName): string
    {
        return sprintf(static::PAGE_CONTEXT_FIELD_PLACEHOLDER, $fieldName);
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
