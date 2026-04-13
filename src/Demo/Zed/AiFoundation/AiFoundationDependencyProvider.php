<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiFoundation;

use Spryker\Zed\AiFoundation\AiFoundationDependencyProvider as SprykerAiFoundationDependencyProvider;
use Spryker\Zed\AiFoundation\Communication\Plugin\AuditLogPostPromptPlugin;
use Spryker\Zed\AiFoundation\Communication\Plugin\AuditLogPostToolCallPlugin;
use Spryker\Zed\AiFoundation\Communication\Plugin\Log\AiInteractionHandlerPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\BackofficeAssistantSsePostToolCallPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\BackofficeAssistantSsePreToolCallPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\DiscountManagementToolSetPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\FormFillToolSetPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\NavigationToolSetPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\OrderDetailsToolSetPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\OrderManagementToolSetPlugin;

class AiFoundationDependencyProvider extends SprykerAiFoundationDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\Log\Dependency\Plugin\LogHandlerPluginInterface>
     */
    protected function getAiInteractionLogHandlerPlugins(): array
    {
        return [
            new AiInteractionHandlerPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Plugin\PreToolCallPluginInterface>
     */
    protected function getPreToolCallPlugins(): array
    {
        return [
            new BackofficeAssistantSsePreToolCallPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Plugin\PostToolCallPluginInterface>
     */
    protected function getPostToolCallPlugins(): array
    {
        return [
            new BackofficeAssistantSsePostToolCallPlugin(),
            new AuditLogPostToolCallPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Plugin\PostPromptPluginInterface>
     */
    protected function getPostPromptPlugins(): array
    {
        return [
            new AuditLogPostPromptPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface>
     */
    protected function getAiToolSetPlugins(): array
    {
        return [
            new NavigationToolSetPlugin(),
            new OrderManagementToolSetPlugin(),
            new OrderDetailsToolSetPlugin(),
            new DiscountManagementToolSetPlugin(),
            new FormFillToolSetPlugin(),
        ];
    }
}
