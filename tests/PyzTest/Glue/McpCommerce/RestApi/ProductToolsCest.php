<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 5 — the customer describes what they need and the assistant finds it, with enough detail to
 * choose without browsing the catalog.
 *
 * Every product here is addressed by SKU. No test may depend on an `id_product_abstract`, which
 * varies with import order (PRD §4.4).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group ProductToolsCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class ProductToolsCest
{
    /**
     * @var string
     */
    protected const SEARCH_TERM = 'camera';

    /**
     * @var string
     */
    protected const SEARCH_TERM_WITHOUT_MATCHES = 'zzzqqqxxnothingmatchesthisterm';

    /**
     * US5-AC1: a search returns at most 12 products, each with a SKU, a name and a price.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function searchReturnsAtMostTwelveProductsWithSkuNamePrice(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $structuredContent = $I->callSuccessfulMcpTool(
            'product_search',
            ['query' => static::SEARCH_TERM],
            $accessToken,
        );

        // Assert
        $products = $structuredContent['products'] ?? [];

        $I->assertIsArray($products);
        $I->assertNotEmpty($products, 'The demo catalog must return at least one match for "camera".');
        $I->assertLessThanOrEqual(12, count($products));
        $I->assertSame(static::SEARCH_TERM, $structuredContent['query'] ?? null);
        $I->assertGreaterThan(0, $structuredContent['totalFound'] ?? 0);

        foreach ($products as $product) {
            $I->assertNotEmpty($product['sku'] ?? null);
            $I->assertNotEmpty($product['name'] ?? null);
            $I->assertNotNull($product['price'] ?? null);
        }

        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US5-AC2: a term matching nothing is a successful tool result with zero products, not an error.
     * An assistant must be able to tell "nothing matched" apart from "the shop is broken".
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function searchWithNoMatchesReturnsEmptyResult(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $structuredContent = $I->callSuccessfulMcpTool(
            'product_search',
            ['query' => static::SEARCH_TERM_WITHOUT_MATCHES],
            $accessToken,
        );

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertSame([], $structuredContent['products'] ?? null);
        $I->assertSame(0, $structuredContent['totalFound'] ?? null);
    }

    /**
     * US5-AC3: the SKU a search reports is usable against the product detail tool, and detail carries
     * the name, price and availability. The SKU is taken from the live search result rather than
     * hardcoded, so this genuinely asserts the two tools compose.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function productDetailBySku(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $searchContent = $I->callSuccessfulMcpTool(
            'product_search',
            ['query' => static::SEARCH_TERM],
            $accessToken,
        );

        $firstProduct = ($searchContent['products'] ?? [])[0] ?? null;
        $I->assertIsArray($firstProduct);

        $searchReportedSku = (string)($firstProduct['sku'] ?? '');
        $I->assertNotSame('', $searchReportedSku);

        // Act
        $structuredContent = $I->callSuccessfulMcpTool(
            'product_detail',
            ['sku' => $searchReportedSku],
            $accessToken,
        );

        // Assert
        $I->assertNotEmpty($structuredContent['sku'] ?? null);
        $I->assertNotEmpty($structuredContent['name'] ?? null);
        $I->assertNotNull($structuredContent['price'] ?? null);
        $I->assertArrayHasKey('isAvailable', $structuredContent);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * The concrete SKU the search hands out for adding to a cart must also resolve to detail, so the
     * assistant can confirm the exact variant it is about to add.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function productDetailByConcreteAddToCartSku(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $structuredContent = $I->callSuccessfulMcpTool(
            'product_detail',
            ['sku' => McpCommerceRestApiTester::CONCRETE_SKU],
            $accessToken,
        );

        // Assert
        $I->assertSame(McpCommerceRestApiTester::CONCRETE_SKU, $structuredContent['sku'] ?? null);
        $I->assertNotNull($structuredContent['price'] ?? null);
    }

    /**
     * An unknown SKU produces a tool error that names the SKU, with no stack trace
     * (PRD §5 Reliability).
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function productDetailForUnknownSkuReturnsToolErrorNamingTheSku(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool('product_detail', ['sku' => 'NOSUCHSKU999'], $accessToken);

        // Assert
        $I->assertTrue($result['isError'] ?? false);
        $I->assertStringContainsString('NOSUCHSKU999', $I->extractToolText($result));
        $I->assertStringNotContainsString('Stack trace', $I->grabResponse());
    }

    /**
     * A search with no term is a tool error rather than an unbounded catalog dump.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function searchWithoutQueryIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $result = $I->callMcpTool('product_search', [], $accessToken);

        // Assert
        $I->assertTrue($result['isError'] ?? false);
    }
}
