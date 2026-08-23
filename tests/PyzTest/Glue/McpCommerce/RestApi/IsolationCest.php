<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * The security-regression set (PRD §4.1 "Security regression", mandatory scenarios 7, 8 and 9).
 *
 * Per PRD §4.3 none of these may be skipped, weakened or marked as an expected failure. Each asserts
 * a real outcome: Customer A's MCP token must not read or mutate Customer B's cart, must not place an
 * order against it, and no MCP payload may ever carry a shop access or refresh token.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group IsolationCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class IsolationCest
{
    /**
     * US6-AC4 / mandatory scenario 7: Customer A's MCP token can neither see nor modify Customer B's
     * cart.
     *
     * Customer B's cart is created out of band with B's own shop credential, so it genuinely belongs
     * to B. A then adds an item using her own MCP token and the assertion checks two things: A's item
     * did not land in B's cart, and A cannot address B's cart directly either.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function customerACannotSeeOrModifyCustomerBCart(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $customerBCartUuid = $I->haveEmptyCartUuid(McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL);
        $customerBItemCountBefore = $I->getCartItemCount(
            $customerBCartUuid,
            McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL,
        );

        $customerAAccessToken = $I->haveMcpAccessToken(McpCommerceRestApiTester::CUSTOMER_EMAIL);

        // Act — Customer A fills her own cart.
        $customerAContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 1],
            $customerAAccessToken,
        );

        // Assert — A's cart is not B's cart, and B's cart is untouched.
        $I->assertNotSame(
            $customerBCartUuid,
            $customerAContent['cartId'] ?? null,
            'Customer A must never be handed Customer B\'s cart.',
        );
        $I->assertSame(
            $customerBItemCountBefore,
            $I->getCartItemCount($customerBCartUuid, McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL),
            'Customer A adding to her cart must not change Customer B\'s cart.',
        );

        // Act — A tries to address B's cart head on, through the only tool that takes a cart id.
        $result = $I->callMcpTool(
            'checkout',
            ['cartId' => $customerBCartUuid],
            $customerAAccessToken,
        );

        // Assert — refused, and still no mutation of B's cart.
        $I->assertTrue(
            $result['isError'] ?? false,
            'Customer A must not be able to operate on Customer B\'s cart.',
        );
        $I->assertSame(
            $customerBItemCountBefore,
            $I->getCartItemCount($customerBCartUuid, McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL),
        );
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US7-AC3 / mandatory scenario 8: Customer A's MCP token cannot place an order against Customer
     * B's cart. B's order count is read before and after from the sales table, so a created order
     * cannot hide behind a filtered API view.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function customerACannotCheckoutCustomerBCart(McpCommerceRestApiTester $I): void
    {
        // Arrange — give B a cart that WOULD be checkout-worthy if it were A's.
        $customerBShopAccessToken = $I->haveShopAccessToken(McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL);
        $customerBCartUuid = $I->haveEmptyCartUuid(McpCommerceRestApiTester::OTHER_CUSTOMER_EMAIL);
        $I->addItemToCartWithShopToken(
            $customerBCartUuid,
            $customerBShopAccessToken,
            McpCommerceRestApiTester::CONCRETE_SKU,
            McpCommerceRestApiTester::QUANTITY_CLEARING_MINIMUM_ORDER,
        );

        $customerBOrderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::OTHER_CUSTOMER_REFERENCE);
        $customerAOrderCountBefore = $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE);

        $customerAAccessToken = $I->haveMcpAccessToken(McpCommerceRestApiTester::CUSTOMER_EMAIL);

        // Act
        $result = $I->callMcpTool('checkout', ['cartId' => $customerBCartUuid], $customerAAccessToken);

        // Assert — refused, and no order created for either customer.
        $I->assertTrue(
            $result['isError'] ?? false,
            'Customer A must not be able to check out Customer B\'s cart.',
        );
        $I->assertArrayNotHasKey('structuredContent', $result);

        $I->assertSame(
            $customerBOrderCountBefore,
            $I->getOrderCount(McpCommerceRestApiTester::OTHER_CUSTOMER_REFERENCE),
            'No order may be created for Customer B by Customer A\'s token.',
        );
        $I->assertSame(
            $customerAOrderCountBefore,
            $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE),
            'No order may be created for Customer A from Customer B\'s cart.',
        );
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * Customer A's order history must contain only her own orders — the order list tool is scoped by
     * the token's identity, never by a tool argument.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function orderListIsScopedToTheAuthorizingCustomer(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $customerAAccessToken = $I->haveMcpAccessToken(McpCommerceRestApiTester::CUSTOMER_EMAIL);

        // Act
        $structuredContent = $I->callSuccessfulMcpTool('order_list', [], $customerAAccessToken);

        // Assert — the count matches Customer A's own orders in the sales table, not the shop's total.
        $I->assertSame(
            $I->getOrderCount(McpCommerceRestApiTester::CUSTOMER_REFERENCE),
            $structuredContent['orderCount'] ?? null,
            'The order list must contain exactly the authorizing customer\'s orders.',
        );

        // Every listed order must genuinely belong to the authorizing customer. Order references are
        // random and carry no customer prefix, so ownership is checked against the sales table.
        foreach ($structuredContent['orders'] ?? [] as $order) {
            $I->assertTrue(
                $I->hasOrderWithReference(
                    McpCommerceRestApiTester::CUSTOMER_REFERENCE,
                    (string)($order['orderReference'] ?? ''),
                ),
                'The order list must not contain an order of another customer.',
            );
        }
    }

    /**
     * Mandatory scenario 9, asserted across the whole MCP surface at once: no success body and no
     * error body of any tool or protocol method may carry a shop access or refresh token.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function noMcpResponseOrErrorPayloadContainsAShopToken(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act + Assert — protocol methods.
        $I->sendMcpRequest('initialize', [], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        $I->sendMcpRequest('tools/list', null, $accessToken);
        $I->dontSeeResponseContainsShopToken();

        // Act + Assert — every tool, on its success path.
        $I->callMcpTool('product_search', ['query' => 'camera'], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        $I->callMcpTool('product_detail', ['sku' => McpCommerceRestApiTester::CONCRETE_SKU], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        $I->callMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 1],
            $accessToken,
        );
        $I->dontSeeResponseContainsShopToken();

        $I->callMcpTool('order_list', [], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        // Act + Assert — every tool, on an error path, where a leaky error handler would show up.
        $I->callMcpTool('product_detail', ['sku' => 'NOSUCHSKU999'], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        $I->callMcpTool('add_to_cart', ['sku' => 'NOSUCHSKU999', 'quantity' => 1], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        $I->callMcpTool('checkout', ['cartId' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'], $accessToken);
        $I->dontSeeResponseContainsShopToken();

        // Act + Assert — the unauthenticated 401, whose header names a discovery URL and nothing else.
        $I->deleteAuthorizationHeader();
        $I->sendMcpRequest('tools/list');
        $I->dontSeeResponseContainsShopToken();
    }
}
