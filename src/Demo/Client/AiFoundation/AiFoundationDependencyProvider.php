<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\AiFoundation;

use Spryker\Client\AiFoundation\AiFoundationDependencyProvider as SprykerAiFoundationDependencyProvider;
use Spryker\Client\AiFoundation\Plugin\AuditLog\AiInteractionAuditLogPostPromptPlugin;
use Spryker\Client\AiFoundation\Plugin\AuditLog\AiInteractionAuditLogPostToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePostToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSsePreToolCallPlugin;
use SprykerFeature\Client\AiCommerce\Plugin\AiFoundation\StorefrontAssistantSseStreamEventPlugin;
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

class AiFoundationDependencyProvider extends SprykerAiFoundationDependencyProvider
{
    /**
     * @return array<\Spryker\Client\AiFoundation\Dependency\Tools\ToolPluginInterface>
     */
    protected function getAiToolPlugins(): array
    {
        return [
            new CatalogSearchToolPlugin(),
            new CatalogSuggestToolPlugin(),
            new CategoryTreeToolPlugin(),
            new DisplayProductsToolPlugin(),
            new ProductDetailsToolPlugin(),
            new ProductRelationsToolPlugin(),
            new ReadShopPageToolPlugin(),
            new CompareProductsToolPlugin(),
            new ProductSetsToolPlugin(),
            new OfferChoicesToolPlugin(),
            new DisplaySetupToolPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\AiFoundation\Dependency\Plugin\PreToolCallPluginInterface>
     */
    protected function getPreToolCallPlugins(): array
    {
        return [
            new StorefrontAssistantSsePreToolCallPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\AiFoundation\Dependency\Plugin\PostToolCallPluginInterface>
     */
    protected function getPostToolCallPlugins(): array
    {
        return [
            new StorefrontAssistantSsePostToolCallPlugin(),
            new AiInteractionAuditLogPostToolCallPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\AiFoundation\Dependency\Plugin\StreamEventPluginInterface>
     */
    protected function getStreamEventPlugins(): array
    {
        return [
            new StorefrontAssistantSseStreamEventPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\AiFoundation\Dependency\Plugin\PostPromptPluginInterface>
     */
    protected function getPostPromptPlugins(): array
    {
        return [
            new AiInteractionAuditLogPostPromptPlugin(),
        ];
    }
}
