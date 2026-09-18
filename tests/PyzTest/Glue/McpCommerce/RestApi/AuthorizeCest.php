<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\RestApi;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use PyzTest\Glue\McpCommerce\McpCommerceRestApiTester;

/**
 * Story 3 (authorization half) — the customer authorizes the AI client in a browser, and only after
 * a successful credential check plus explicit approval is a code handed back.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group AuthorizeCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class AuthorizeCest
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
     * US3-AC1: a valid credential plus approval redirects back to the registered redirect URI with a
     * single-use code and the caller's `state` echoed unchanged (the client's CSRF defence).
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function customerLoginAndApprovalIssuesCode(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $state = 'pyztest-state-' . bin2hex(random_bytes(4));

        // Act
        $I->sendAuthorizeRequest(
            $clientIdentifier,
            $I->createCodeChallenge($codeVerifier),
            McpCommerceRestApiTester::CUSTOMER_EMAIL,
            $state,
        );

        // Assert
        $redirectTargetUrl = $I->grabRedirectTargetUrl();

        $I->assertStringContainsString(McpCommerceRestApiTester::REDIRECT_URI_PATH, $redirectTargetUrl);
        $I->assertNotEmpty($I->grabAuthorizationCodeFromLocationHeader());
        $I->assertSame($state, $I->grabQueryParameterFromLocationHeader('state'));

        // The redirect must never carry a shop token.
        $I->assertStringNotContainsString('eyJ', $redirectTargetUrl);
    }

    /**
     * The consent screen must be rendered for a GET, so a real browser-based client can show it.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function authorizeEndpointRendersConsentScreen(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $codeChallenge = $I->createCodeChallenge($I->createCodeVerifier());

        // Act
        $I->sendGet(McpCommerceConstants::PATH_AUTHORIZE, [
            'response_type' => 'code',
            'client_id' => $clientIdentifier,
            'redirect_uri' => McpCommerceRestApiTester::REDIRECT_URI,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => 'pyztest-state',
        ]);

        // Assert
        $I->seeResponseCodeIs(200);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * A wrong password must not produce an authorization code.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function wrongCredentialsIssueNoAuthorizationCode(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $codeChallenge = $I->createCodeChallenge($I->createCodeVerifier());

        $I->stopFollowingRedirects();
        $I->useFormUrlEncodedContentType();

        // Act
        $I->sendPost(McpCommerceConstants::PATH_AUTHORIZE, [
            'response_type' => 'code',
            'client_id' => $clientIdentifier,
            'redirect_uri' => McpCommerceRestApiTester::REDIRECT_URI,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => 'pyztest-state',
            'email' => McpCommerceRestApiTester::CUSTOMER_EMAIL,
            'password' => 'definitely-not-the-password',
            'approve' => 'yes',
        ]);

        // Assert
        $I->assertNull(
            $I->grabAuthorizationCodeFromLocationHeader(),
            'A failed credential check must not issue an authorization code.',
        );
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * An unknown client must not be able to start an authorization it never registered for.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unknownClientIssuesNoAuthorizationCode(McpCommerceRestApiTester $I): void
    {
        // Act
        $I->sendAuthorizeRequest(
            'mcp-this-client-was-never-registered',
            $I->createCodeChallenge($I->createCodeVerifier()),
        );

        // Assert
        $I->assertNull($I->grabAuthorizationCodeFromLocationHeader());
    }
}
