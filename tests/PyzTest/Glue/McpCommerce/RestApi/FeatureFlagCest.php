<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use Codeception\Util\HttpCode;
use Demo\Shared\McpCommerce\McpCommerceConstants;
use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 8 — an operator can turn the whole MCP surface off, and back on, without a deployment.
 *
 * The flag is flipped through the key-value entry the Glue read path actually resolves, and the write
 * is read back before any assertion runs (see
 * {@see \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester::setFeatureFlag()}). A database-only flip
 * would not propagate and would produce a false result, so it is deliberately not used here.
 *
 * Every test restores the flag to ON in `_after`, so a failure mid-test cannot leave the environment
 * with the feature disabled.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group FeatureFlagCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class FeatureFlagCest
{
    /**
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function _after(McpCommerceRestApiTester $I): void
    {
        $I->setFeatureFlag(true);
    }

    /**
     * US8-AC2 / mandatory scenario 10 (the `/mcp` half): with the flag off the MCP endpoint is gone —
     * 404, not 401. The surface becomes invisible rather than merely unusable, so a client cannot even
     * tell an MCP server was ever there.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function mcpReturns404WhenDisabled(McpCommerceRestApiTester $I): void
    {
        // Arrange — prove the endpoint answers while the flag is on, so the 404 below means something.
        $I->setFeatureFlag(true);
        $I->sendMcpRequest('tools/list');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

        // Act
        $I->setFeatureFlag(false);
        $I->sendMcpRequest('tools/list');

        // Assert
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->assertStringNotContainsString('product_search', $I->grabResponse());
    }

    /**
     * US1-AC4 / mandatory scenario 10 (the metadata half): with the flag off BOTH discovery documents
     * are 404, so a cold-start client finds nothing to bootstrap against.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function metadataReturns404WhenDisabled(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $I->setFeatureFlag(true);
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA);
        $I->seeResponseCodeIs(HttpCode::OK);

        // Act
        $I->setFeatureFlag(false);

        // Assert
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->sendGet(McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /**
     * US8-AC3: re-enabling restores service without a deployment — the same requests that 404'd
     * answer again immediately after the flag goes back on.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function reEnablingRestoresService(McpCommerceRestApiTester $I): void
    {
        // Arrange — off, and confirmed off.
        $I->setFeatureFlag(false);
        $I->sendMcpRequest('tools/list');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        // Act
        $I->setFeatureFlag(true);

        // Assert — the protocol endpoint and both discovery documents are all back.
        $I->sendMcpRequest('tools/list');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeHttpHeader('WWW-Authenticate');

        $I->sendGet(McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA);
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->sendGet(McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA);
        $I->seeResponseCodeIs(HttpCode::OK);

        // And a full authorization plus tool call works again end to end, not just the routing.
        $accessToken = $I->haveMcpAccessToken();
        $I->sendMcpRequest('tools/list', null, $accessToken);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertCount(5, $I->grabResponseJson()['result']['tools'] ?? []);
    }
}
