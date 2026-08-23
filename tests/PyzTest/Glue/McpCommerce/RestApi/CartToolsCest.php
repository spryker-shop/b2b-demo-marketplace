<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 6 — the assistant builds a cart on the customer's behalf, at the quantity asked for, and a
 * bad SKU never leaves the cart in a surprising state.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group CartToolsCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class CartToolsCest
{
    /**
     * The feature ships fail-closed, so a fresh environment (CI included) has the flag OFF and every
     * MCP endpoint 404s. Enabling it here makes each spec self-sufficient instead of depending on
     * ambient state a developer happened to leave enabled.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function _before(McpCommerceRestApiTester $I): void
    {
        $I->setFeatureFlag(true);
    }

    /**
     * US6-AC1: an item is added at the requested quantity and the tool reports the cart identifier,
     * the single line and the cart total.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function addToCartQuantityTwo(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $structuredContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            [
                'sku' => McpCommerceRestApiTester::CONCRETE_SKU,
                'quantity' => McpCommerceRestApiTester::QUANTITY_CLEARING_MINIMUM_ORDER,
            ],
            $accessToken,
        );

        // Assert
        $I->assertNotEmpty($structuredContent['cartId'] ?? null);
        $I->assertNotNull($structuredContent['cartTotal'] ?? null);

        $items = $structuredContent['items'] ?? [];
        $I->assertIsArray($items);

        $addedItem = $this->findItemBySku($items, McpCommerceRestApiTester::CONCRETE_SKU);
        $I->assertNotNull($addedItem, 'The added SKU must appear as a cart line.');
        $I->assertGreaterThanOrEqual(
            McpCommerceRestApiTester::QUANTITY_CLEARING_MINIMUM_ORDER,
            $addedItem['quantity'] ?? 0,
        );

        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US6-AC2: re-adding the same SKU increases the quantity on the existing line rather than
     * creating a second line. The delta is asserted, so a pre-existing cart cannot mask the merge.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function reAddingSameSkuIncreasesQuantity(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        $firstContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 2],
            $accessToken,
        );
        $quantityAfterFirstAdd = (int)($this->findItemBySku(
            $firstContent['items'] ?? [],
            McpCommerceRestApiTester::CONCRETE_SKU,
        )['quantity'] ?? 0);
        $lineCountAfterFirstAdd = count($firstContent['items'] ?? []);

        // Act
        $secondContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 1],
            $accessToken,
        );

        // Assert
        $quantityAfterSecondAdd = (int)($this->findItemBySku(
            $secondContent['items'] ?? [],
            McpCommerceRestApiTester::CONCRETE_SKU,
        )['quantity'] ?? 0);

        $I->assertSame(
            $quantityAfterFirstAdd + 1,
            $quantityAfterSecondAdd,
            'Re-adding the same SKU must increase the existing line quantity by the added amount.',
        );
        $I->assertSame(
            $lineCountAfterFirstAdd,
            count($secondContent['items'] ?? []),
            'Re-adding the same SKU must not create a second cart line.',
        );
        $I->assertSame($firstContent['cartId'] ?? null, $secondContent['cartId'] ?? null);
    }

    /**
     * A non-numeric quantity must be REJECTED, never silently coerced to the default. Found in manual
     * QA: `quantity: "abc"` quietly became 1, so the tool added something the caller never asked for.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function nonNumericQuantityIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 'abc'],
            $accessToken,
        );

        // Assert
        $I->assertTrue(
            $result['isError'] ?? false,
            'A non-numeric quantity must be a tool error, not a silent fallback to the default.',
        );
    }

    /**
     * Quantity must be bounded. Found in manual QA: 999999999 was accepted and produced a
     * multi-billion-euro cart.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function absurdlyLargeQuantityIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 999999999],
            $accessToken,
        );

        // Assert
        $I->assertTrue(
            $result['isError'] ?? false,
            'An unbounded quantity must be refused rather than driving the cart into an absurd state.',
        );
    }

    /**
     * US6-AC3 / mandatory scenario 11: an unknown SKU is a tool error naming the SKU, and the cart is
     * genuinely unchanged. The item count is read back from the cart itself, so an error message
     * alone cannot satisfy this test.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unknownSkuLeavesCartUnchanged(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $cartContent = $I->callSuccessfulMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 1],
            $accessToken,
        );
        $cartUuid = (string)($cartContent['cartId'] ?? '');
        $I->assertNotSame('', $cartUuid);

        $itemCountBefore = $I->getCartItemCount($cartUuid, McpCommerceRestApiTester::CUSTOMER_EMAIL);

        // Act
        $result = $I->callMcpTool(
            'add_to_cart',
            ['sku' => 'NOSUCHSKU999', 'quantity' => 1],
            $accessToken,
        );

        // Assert
        $I->assertTrue($result['isError'] ?? false, 'An unknown SKU must be a tool error.');
        $I->assertStringContainsString('NOSUCHSKU999', $I->extractToolText($result));
        $I->assertStringNotContainsString('Stack trace', $I->grabResponse());

        $I->assertSame(
            $itemCountBefore,
            $I->getCartItemCount($cartUuid, McpCommerceRestApiTester::CUSTOMER_EMAIL),
            'A rejected SKU must leave the cart item count unchanged.',
        );
    }

    /**
     * A cart line cannot be created with a nonsensical quantity.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function nonPositiveQuantityIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool(
            'add_to_cart',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU, 'quantity' => 0],
            $accessToken,
        );

        // Assert
        $I->assertTrue($result['isError'] ?? false);
    }

    /**
     * A missing SKU is a tool error rather than a silent no-op.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function missingSkuIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool('add_to_cart', ['quantity' => 1], $accessToken);

        // Assert
        $I->assertTrue($result['isError'] ?? false);
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return array<string, mixed>|null
     */
    protected function findItemBySku(array $items, string $sku): ?array
    {
        foreach ($items as $item) {
            if (is_array($item) && ($item['sku'] ?? null) === $sku) {
                return $item;
            }
        }

        return null;
    }
}
