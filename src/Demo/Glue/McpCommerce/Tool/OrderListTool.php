<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Lists the authorizing customer's orders.
 *
 * Wraps the existing `GET /customers/{customerReference}/orders` Storefront resource. Its `security:`
 * expression additionally votes on `CUSTOMER_OWNER` for the `customerReference` request attribute, so
 * the customer reference sent here always comes from the validated MCP identity and never from the
 * tool arguments — an assistant therefore cannot list another customer's orders.
 */
class OrderListTool extends AbstractTool
{
    /**
     * @var string
     */
    protected const TOOL_NAME = 'order_list';

    /**
     * @var string
     */
    protected const PATH_CUSTOMER_ORDERS = '/customers/%s/orders?page[limit]=%d';

    /**
     * How many orders to request from the orders resource.
     *
     * The resource defaults to 10 per page and returns them OLDEST first, so without an explicit limit
     * a customer with a longer history would never see the order they just placed — the newest orders
     * sit on a later page. Requesting a generous single page keeps the tool's answer complete while
     * still bounding the response.
     *
     * @var int
     */
    protected const ORDER_PAGE_LIMIT = 100;

    /**
     * @var string
     */
    protected const ATTRIBUTE_ORDER_REFERENCE = 'orderReference';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CREATED_AT = 'createdAt';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CURRENCY_ISO_CODE = 'currencyIsoCode';

    /**
     * @var string
     */
    protected const ATTRIBUTE_TOTALS = 'totals';

    /**
     * @var string
     */
    protected const TOTALS_KEY_GRAND_TOTAL = 'grandTotal';

    /**
     * @var string
     */
    protected const RESULT_KEY_ORDERS = 'orders';

    /**
     * @var string
     */
    protected const RESULT_KEY_ORDER_COUNT = 'orderCount';

    /**
     * @var string
     */
    protected const RESULT_KEY_ORDER_REFERENCE = 'orderReference';

    /**
     * @var string
     */
    protected const RESULT_KEY_TOTAL = 'total';

    /**
     * @var string
     */
    protected const RESULT_KEY_CURRENCY = 'currency';

    /**
     * @var string
     */
    protected const RESULT_KEY_CREATED_AT = 'createdAt';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CUSTOMER_REFERENCE = 'The customer identity could not be resolved.';

    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    public function getDescription(): string
    {
        return 'Lists the customer\'s previous orders with their order reference, total and creation '
            . 'date. Takes no arguments and always returns only the authorizing customer\'s orders.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return [
            static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_OBJECT,
            static::SCHEMA_KEY_PROPERTIES => [],
        ];
    }

    /**
     * The customer reference always comes from the validated MCP identity, never from `$arguments`,
     * which this tool deliberately ignores: accepting one would let a client ask for another
     * customer's orders.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(McpIdentityTransfer $mcpIdentityTransfer, array $arguments): ToolResult
    {
        unset($arguments);

        $customerReference = (string)$mcpIdentityTransfer->getCustomerReference();

        if (trim($customerReference) === '') {
            return ToolResult::createError(static::ERROR_MESSAGE_MISSING_CUSTOMER_REFERENCE);
        }

        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(
                static::PATH_CUSTOMER_ORDERS,
                rawurlencode($customerReference),
                static::ORDER_PAGE_LIMIT,
            ),
            static::METHOD_GET,
            $this->createIdentityClaims($mcpIdentityTransfer),
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return ToolResult::createError($this->extractErrorMessage($storefrontSubRequestResult));
        }

        $orders = $this->mapOrders($this->extractResourceCollection($storefrontSubRequestResult));

        return ToolResult::createSuccess([
            static::RESULT_KEY_ORDER_COUNT => count($orders),
            static::RESULT_KEY_ORDERS => $orders,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapOrders(array $resources): array
    {
        $orders = [];

        foreach ($resources as $resource) {
            $attributes = $resource[static::PAYLOAD_KEY_ATTRIBUTES] ?? null;

            if (!is_array($attributes)) {
                continue;
            }

            $orders[] = [
                static::RESULT_KEY_ORDER_REFERENCE => (string)($attributes[static::ATTRIBUTE_ORDER_REFERENCE] ?? ''),
                static::RESULT_KEY_TOTAL => $this->extractGrandTotal($attributes),
                static::RESULT_KEY_CURRENCY => (string)($attributes[static::ATTRIBUTE_CURRENCY_ISO_CODE] ?? ''),
                static::RESULT_KEY_CREATED_AT => (string)($attributes[static::ATTRIBUTE_CREATED_AT] ?? ''),
            ];
        }

        return $orders;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function extractGrandTotal(array $attributes): ?int
    {
        $totals = $attributes[static::ATTRIBUTE_TOTALS] ?? null;

        if (!is_array($totals) || !isset($totals[static::TOTALS_KEY_GRAND_TOTAL])) {
            return null;
        }

        return (int)$totals[static::TOTALS_KEY_GRAND_TOTAL];
    }
}
