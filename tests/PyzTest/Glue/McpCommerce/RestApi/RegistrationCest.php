<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use Codeception\Util\HttpCode;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 2 — an AI client registers itself without any pre-provisioning, and the allow-list keeps a
 * hostile redirect URI from ever becoming a registered client.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group RegistrationCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class RegistrationCest
{
    /**
     * US2-AC1: a client registers itself and is issued a unique identifier, declared as a public
     * client (no secret) that must use PKCE with S256.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function newClientRegistersSuccessfully(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendRegistrationRequest([
            'client_name' => 'PyzTest MCP client',
            'redirect_uris' => [McpCommerceRestApiTester::REDIRECT_URI],
        ]);

        // Assert
        $I->seeResponseCodeIs(HttpCode::CREATED);

        $registration = $I->grabResponseJson();

        $I->assertNotEmpty($registration['client_id'] ?? null);
        $I->assertSame('none', $registration['token_endpoint_auth_method'] ?? null);
        $I->assertSame('S256', $registration['code_challenge_method'] ?? null);
        $I->assertSame(['authorization_code'], $registration['grant_types'] ?? null);

        // A public client must never be issued a secret.
        $I->assertArrayNotHasKey('client_secret', $registration);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * Two registrations must never collide on the same identifier.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function eachRegistrationIssuesAUniqueClientIdentifier(McpCommerceRestApiTester $I): void
    {
        // Act
        $firstClientIdentifier = $I->haveRegisteredClientIdentifier();
        $secondClientIdentifier = $I->haveRegisteredClientIdentifier();

        // Assert
        $I->assertNotSame($firstClientIdentifier, $secondClientIdentifier);
    }

    /**
     * US2-AC2: a registration without a redirect URI is refused with a message that names the
     * offending field, so the client can correct it without guessing.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function registrationRejectedWithoutRedirectUri(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendRegistrationRequest(['client_name' => 'PyzTest MCP client without redirect']);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertStringContainsString('redirect_uris', $I->grabResponse());
    }

    /**
     * US2-AC3 / mandatory scenario 6: a redirect URI outside the allow-list is rejected AND no client
     * record is created. The row count is read before and after, so a silently-persisted client
     * cannot pass this test.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function registrationRejectsNonAllowlistedRedirectUri(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $clientCountBefore = SpyOauthClientQuery::create()->count();

        // Act
        $I->sendRegistrationRequest([
            'client_name' => 'PyzTest hostile client',
            'redirect_uris' => ['https://attacker.example.com/steal'],
        ]);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertSame(
            $clientCountBefore,
            SpyOauthClientQuery::create()->count(),
            'A registration with a non-allowlisted redirect URI must not create a client record.',
        );
    }

    /**
     * The allow-list must not be bypassable by smuggling a hostile URI alongside a permitted one.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function registrationRejectsHostileRedirectUriSmuggledBesideAnAllowedOne(
        McpCommerceRestApiTester $I,
    ): void {
        // Arrange
        $clientCountBefore = SpyOauthClientQuery::create()->count();

        // Act
        $I->sendRegistrationRequest([
            'client_name' => 'PyzTest smuggling client',
            'redirect_uris' => [
                McpCommerceRestApiTester::REDIRECT_URI,
                'https://attacker.example.com/steal',
            ],
        ]);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertSame($clientCountBefore, SpyOauthClientQuery::create()->count());
    }
}
