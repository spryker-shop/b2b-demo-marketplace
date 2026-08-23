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
 * Story 1 — an MCP client with no credentials must be able to discover the server and how to
 * authorize against it, using nothing but the protocol's own conventions.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group DiscoveryCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class DiscoveryCest
{
    /**
     * US1-AC1: an unauthenticated tool call is refused with 401 and a `WWW-Authenticate` header that
     * names the protected-resource metadata document, which is how a cold-start client finds the
     * authorization server at all.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unauthenticatedMcpCallReturns401WithWwwAuthenticate(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendMcpRequest('tools/list');

        // Assert
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeHttpHeader('WWW-Authenticate');

        $wwwAuthenticateHeader = $I->grabHeaderAsString('WWW-Authenticate');
        $I->assertStringContainsString('Bearer', $wwwAuthenticateHeader);
        $I->assertStringContainsString(
            McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA,
            $wwwAuthenticateHeader,
            'WWW-Authenticate must point at the protected-resource metadata document.',
        );

        // No tool may be listed to an unauthenticated caller.
        $I->assertStringNotContainsString('product_search', $I->grabResponse());
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US1-AC2: the RFC 8414 authorization server metadata document tells the client where to
     * register, authorize and exchange a token, and that PKCE with S256 is mandatory.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function authorizationServerMetadataIsPublished(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);

        $metadata = $I->grabResponseJson();

        $I->assertNotEmpty($metadata['issuer'] ?? null);
        $I->assertStringEndsWith(
            McpCommerceConstants::PATH_AUTHORIZE,
            (string)($metadata['authorization_endpoint'] ?? ''),
        );
        $I->assertStringEndsWith(
            McpCommerceConstants::PATH_TOKEN,
            (string)($metadata['token_endpoint'] ?? ''),
        );
        $I->assertStringEndsWith(
            McpCommerceConstants::PATH_REGISTER,
            (string)($metadata['registration_endpoint'] ?? ''),
        );
        $I->assertSame(['authorization_code'], $metadata['grant_types_supported'] ?? null);
        $I->assertSame(['S256'], $metadata['code_challenge_methods_supported'] ?? null);

        // PRD §5: the discovery documents are publicly cacheable for an hour.
        $I->assertStringContainsString('max-age=3600', $I->grabHeaderAsString('Cache-Control'));
    }

    /**
     * US1-AC3: the RFC 9728 protected resource metadata document names the MCP endpoint as the
     * protected resource and points back at the issuer as its authorization server.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function protectedResourceMetadataPointsToIssuer(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);

        $metadata = $I->grabResponseJson();

        $I->assertStringEndsWith(McpCommerceConstants::PATH_MCP, (string)($metadata['resource'] ?? ''));
        $I->assertSame(['header'], $metadata['bearer_methods_supported'] ?? null);

        $authorizationServers = $metadata['authorization_servers'] ?? [];
        $I->assertIsArray($authorizationServers);
        $I->assertNotEmpty($authorizationServers);
    }

    /**
     * The two documents must agree: the authorization server the protected resource points at is the
     * issuer that publishes the authorization server metadata. A client that follows the chain
     * mechanically would otherwise loop.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function bothMetadataDocumentsAgreeOnTheIssuer(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA);
        $issuer = (string)($I->grabResponseJson()['issuer'] ?? '');

        // Act
        $I->sendGet(McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA);

        // Assert
        $I->assertSame([$issuer], $I->grabResponseJson()['authorization_servers'] ?? null);
    }
}
