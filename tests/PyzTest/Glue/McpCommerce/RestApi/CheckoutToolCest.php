<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 7 — the assistant places the order and the customer can see it afterwards.
 *
 * Checkout runs as `DE--2`. `spencor.hopkin@acme.com` cannot be used: a B2B purchasing-limit
 * permission refuses order placement, and the shop enforces a EUR 40 minimum order, so the cart is
 * always filled past that threshold by SKU rather than by an import-order-dependent id (PRD §4.4).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group CheckoutToolCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class CheckoutToolCest
{
    /**
     * US7-AC1: a cart with an item is checked out and an order reference comes back. The sales table
     * is counted before and after, so the order must genuinely exist.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function orderPlacedForSingleItemCart(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $cartContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            [
                'sku' => McpCommerceRestApiTester::CONCRETE_SKU,
                'quantity' => McpCommerceRestApiTester::QUANTITY_CLEARING_MINIMUM_ORDER,
            ],
            $accessToken,
        );
        $cartUuid = (string)($cartContent['cartId'] ?? '');
        $I->assertNotSame('', $cartUuid);

        $orderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE);

        // Act
        $structuredContent = $I->callSuccessfulMcpTool('checkout', ['cartId' => $cartUuid], $accessToken);

        // Assert
        $orderReference = (string)($structuredContent['orderReference'] ?? '');

        $I->assertNotSame('', $orderReference, 'Checkout must return an order reference.');
        $I->assertSame($cartUuid, $structuredContent['cartId'] ?? null);

        // The reference must belong to an order of THIS customer, which is what makes the count below
        // attributable rather than merely coincidental.
        $I->assertTrue(
            $I->hasOrderWithReference(McpCommerceRestApiTester::CUSTOMER_REFERENCE, $orderReference),
            'The returned order reference must belong to the authorizing customer.',
        );

        $I->assertSame(
            $orderCountBefore + 1,
            $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE),
            'Exactly one order must be created.',
        );
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US7-AC2 / mandatory scenario 12: checking out an EXISTING but EMPTY cart is refused and creates
     * no order.
     *
     * The cart is created for real through the Storefront API and deliberately left empty. A
     * nonexistent cart uuid would only prove "cart not found", which is a different code path and
     * would not cover this criterion.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function emptyCartCheckoutCreatesNoOrder(McpCommerceRestApiTester $I): void
    {
        // Arrange — a real, owned, genuinely empty cart.
        $emptyCartUuid = $I->haveEmptyCartUuid(McpCommerceRestApiTester::CUSTOMER_EMAIL);

        $I->assertSame(
            0,
            $I->getCartItemCount($emptyCartUuid, McpCommerceRestApiTester::CUSTOMER_EMAIL),
            'The precondition of this test is an EXISTING cart with zero items.',
        );

        $accessToken = $I->haveMcpAccessToken();
        $orderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE);

        // Act
        $result = $I->callMcpTool('checkout', ['cartId' => $emptyCartUuid], $accessToken);

        // Assert — refused, with no order reference and no order row.
        $I->assertTrue($result['isError'] ?? false, 'An empty cart must not be checked out.');
        $I->assertArrayNotHasKey('structuredContent', $result);
        $I->assertStringNotContainsString('orderReference', $I->grabResponse());

        $I->assertSame(
            $orderCountBefore,
            $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE),
            'Checking out an empty cart must create no order.',
        );
        $I->assertStringNotContainsString('Stack trace', $I->grabResponse());
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US7-AC4: the order the assistant just placed is visible in the customer's order history, with
     * its reference, total and currency.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function orderListReturnsPlacedOrder(McpCommerceRestApiTester $I): void
    {
        // Arrange — place a fresh order so the assertion targets a known reference.
        $accessToken = $I->haveMcpAccessToken();
        $cartContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            [
                'sku' => McpCommerceRestApiTester::CONCRETE_SKU,
                'quantity' => McpCommerceRestApiTester::QUANTITY_CLEARING_MINIMUM_ORDER,
            ],
            $accessToken,
        );
        $checkoutContent = $I->callSuccessfulMcpTool(
            'checkout',
            ['cartId' => $cartContent['cartId'] ?? ''],
            $accessToken,
        );
        $placedOrderReference = (string)($checkoutContent['orderReference'] ?? '');
        $I->assertNotSame('', $placedOrderReference);

        // Act
        $structuredContent = $I->callSuccessfulMcpTool('order_list', [], $accessToken);

        // Assert
        $orders = $structuredContent['orders'] ?? [];
        $I->assertIsArray($orders);
        $I->assertGreaterThan(0, $structuredContent['orderCount'] ?? 0);

        $placedOrder = null;

        foreach ($orders as $order) {
            if (!is_array($order) || !(($order['orderReference'] ?? null) === $placedOrderReference)) {
                continue;
            }

            $placedOrder = $order;
        }

        $I->assertNotNull($placedOrder, 'The order just placed must appear in the order history.');
        $I->assertNotNull($placedOrder['total'] ?? null);
        $I->assertSame('EUR', $placedOrder['currency'] ?? null);
        $I->assertNotEmpty($placedOrder['createdAt'] ?? null);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * A cart that does not exist is refused. Distinct from the empty-cart path above, and asserted
     * separately so neither can stand in for the other.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function checkoutOfNonexistentCartIsRefused(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $orderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE);

        // Act
        $result = $I->callMcpTool(
            'checkout',
            ['cartId' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'],
            $accessToken,
        );

        // Assert
        $I->assertTrue($result['isError'] ?? false);
        $I->assertSame($orderCountBefore, $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE));
    }

    /**
     * Checkout without a cart identifier is a tool error, never an order against an arbitrary cart.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function checkoutWithoutCartIdIsRefused(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $orderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE);

        // Act
        $result = $I->callMcpTool('checkout', [], $accessToken);

        // Assert
        $I->assertTrue($result['isError'] ?? false);
        $I->assertSame($orderCountBefore, $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE));
    }
}
