<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiFoundation;

use Demo\Zed\BackofficeAssistant\Communication\Plugin\Tool\OrderToolSetPlugin;
use Spryker\Zed\AiFoundation\AiFoundationDependencyProvider as SprykerAiFoundationDependencyProvider;
use Spryker\Zed\AiFoundation\Communication\Plugin\AuditLogPostPromptPlugin;
use Spryker\Zed\AiFoundation\Communication\Plugin\Log\AiInteractionHandlerPlugin;

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
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface>
     */
    protected function getAiToolSetPlugins(): array
    {
        return [
            new OrderToolSetPlugin(),
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
}
