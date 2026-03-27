<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Plugin\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\BackofficeAssistant\Communication\BackofficeAssistantCommunicationFactory getFactory()
 */
class ListRecentOrdersToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public function getName(): string
    {
        return 'list_recent_orders';
    }

    public function getDescription(): string
    {
        return 'Lists the most recent orders';
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     *
     * @param mixed ...$arguments
     *
     * @return mixed
     */
    public function execute(mixed ...$arguments): mixed
    {
        $limit = 5;
        $orders = [];

        $statuses = ['payment pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $names = ['John Doe', 'Jane Smith', 'Bob Wilson', 'Alice Brown', 'Charlie Davis'];

        for ($i = 1; $i <= $limit; $i++) {
            $orders[] = [
                'order_reference' => sprintf('DE--%d', $i),
                'status' => $statuses[($i - 1) % count($statuses)],
                'created_at' => sprintf('2026-03-%02dT10:00:00Z', max(1, 13 - $i)),
                'customer_name' => $names[($i - 1) % count($names)],
                'grand_total' => rand(1000, 50000),
                'currency' => 'EUR',
                'item_count' => rand(1, 10),
            ];
        }

        return json_encode($orders, JSON_THROW_ON_ERROR);
    }
}
