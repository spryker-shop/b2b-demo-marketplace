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
class GetOrderDetailToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public function getName(): string
    {
        return 'get_order_detail';
    }

    public function getDescription(): string
    {
        return 'Retrieves details of a specific order by order reference';
    }

    /**
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new OrderToolParameter(),
        ];
    }

    public function execute(mixed ...$arguments): mixed
    {
        $orderReference = $arguments['order_reference'] ?? 'UNKNOWN';

        return json_encode([
            'order_reference' => $orderReference,
            'status' => 'payment pending',
            'created_at' => '2026-03-10T14:30:00Z',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ],
            'items' => [
                [
                    'sku' => 'DEMO-SKU-001',
                    'name' => 'Wireless Mouse',
                    'quantity' => 2,
                    'unit_price' => 2499,
                ],
                [
                    'sku' => 'DEMO-SKU-002',
                    'name' => 'USB-C Hub',
                    'quantity' => 1,
                    'unit_price' => 4999,
                ],
            ],
            'totals' => [
                'subtotal' => 9997,
                'discount' => 500,
                'grand_total' => 9497,
                'currency' => 'EUR',
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
