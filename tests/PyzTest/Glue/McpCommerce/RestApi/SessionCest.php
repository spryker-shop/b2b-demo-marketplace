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
 * Story 4 — an authorized client opens a session and discovers the tool surface, and a credential
 * that is no longer good is refused before any tool can run.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group SessionCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class SessionCest
{
    /**
     * @var array<string>
     */
    protected const EXPECTED_TOOL_NAMES = [
        'product_search',
        'product_detail',
        'add_to_cart',
        'checkout',
        'order_list',
    ];

    /**
     * US4-AC1: `initialize` negotiates the protocol revision and advertises the tools capability.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function initializeReturnsProtocolVersionAndCapabilities(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $I->sendMcpRequest('initialize', ['protocolVersion' => McpCommerceConstants::PROTOCOL_VERSION], $accessToken);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);

        $result = $I->grabResponseJson()['result'] ?? [];

        $I->assertSame(McpCommerceConstants::PROTOCOL_VERSION, $result['protocolVersion'] ?? null);
        $I->assertArrayHasKey('tools', $result['capabilities'] ?? []);
        $I->assertSame(McpCommerceConstants::SERVER_NAME, $result['serverInfo']['name'] ?? null);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US4-AC2: exactly the five commerce tools are advertised, each with the name, description and
     * input schema an AI client needs in order to call it without documentation.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function toolsListReturnsExactlyFiveTools(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $I->sendMcpRequest('tools/list', null, $accessToken);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);

        $tools = $I->grabResponseJson()['result']['tools'] ?? [];

        $I->assertIsArray($tools);
        $I->assertCount(5, $tools);

        $toolNames = [];

        foreach ($tools as $tool) {
            $I->assertNotEmpty($tool['name'] ?? null);
            $I->assertNotEmpty($tool['description'] ?? null);
            $I->assertIsArray($tool['inputSchema'] ?? null);
            $I->assertSame('object', $tool['inputSchema']['type'] ?? null);

            $toolNames[] = $tool['name'];
        }

        sort($toolNames);
        $expectedToolNames = static::EXPECTED_TOOL_NAMES;
        sort($expectedToolNames);

        $I->assertSame($expectedToolNames, $toolNames);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US4-AC3 / mandatory scenario 2: an expired MCP token is refused on a tool call, with no tool
     * list and no tool execution.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function expiredTokenIsRefused(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();
        $I->expireMcpAccessToken($accessToken);

        // Act
        $I->sendMcpRequest(
            'tools/call',
            ['name' => 'product_search', 'arguments' => ['query' => 'camera']],
            $accessToken,
        );

        // Assert
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeHttpHeader('WWW-Authenticate');
        $I->assertStringNotContainsString('structuredContent', $I->grabResponse());
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * Mandatory scenario 3: a revoked MCP token is refused on a tool call. The same token worked
     * immediately before revocation, so this asserts the revocation and not merely a bad credential.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function revokedTokenIsRefused(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        $I->sendMcpRequest('tools/list', null, $accessToken);
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->revokeMcpAccessToken($accessToken);

        // Act
        $I->sendMcpRequest(
            'tools/call',
            ['name' => 'product_search', 'arguments' => ['query' => 'camera']],
            $accessToken,
        );

        // Assert
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeHttpHeader('WWW-Authenticate');
        $I->assertStringNotContainsString('structuredContent', $I->grabResponse());
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * A credential that was never issued must be refused exactly like a missing one — the caller must
     * not be able to tell the difference.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unknownTokenIsRefused(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendMcpRequest('tools/list', null, 'this-token-was-never-issued');

        // Assert
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeHttpHeader('WWW-Authenticate');
        $I->assertStringNotContainsString('product_search', $I->grabResponse());
    }

    /**
     * An unknown tool name must produce a JSON-RPC error rather than a silent success.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unknownToolNameIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        // Act
        $I->sendMcpRequest('tools/call', ['name' => 'drop_database', 'arguments' => []], $accessToken);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->assertArrayHasKey('error', $I->grabResponseJson());
        $I->assertArrayNotHasKey('result', $I->grabResponseJson());
    }

    /**
     * A malformed JSON-RPC envelope must be answered with a parse error, not a stack trace
     * (PRD §5 Reliability).
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function malformedJsonRpcEnvelopeReturnsParseError(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $accessToken = $I->haveMcpAccessToken();

        $I->useJsonContentType();
        $I->haveHttpHeader(McpCommerceRestApiTester::HEADER_AUTHORIZATION, 'Bearer ' . $accessToken);

        // Act
        $I->sendPost(McpCommerceConstants::PATH_MCP, 'this is not json');

        // Assert
        $I->assertArrayHasKey('error', $I->grabResponseJson());
        $I->assertStringNotContainsString('Stack trace', $I->grabResponse());
        $I->assertStringNotContainsString('#0 /data/', $I->grabResponse());
    }
}
