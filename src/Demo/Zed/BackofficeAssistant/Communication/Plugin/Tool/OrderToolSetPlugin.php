<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Plugin\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;

class OrderToolSetPlugin implements ToolSetPluginInterface
{
    public const string NAME = 'order_management';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface>
     */
    public function getTools(): array
    {
        return [
            new GetOrderDetailToolPlugin(),
            new ListRecentOrdersToolPlugin(),
        ];
    }
}
