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
 * Story 3 (token half) — the code-for-token exchange. This is the security hinge of the whole
 * feature: PKCE binding, single-use codes, and the guarantee that what comes back is an opaque MCP
 * credential and never the customer's shop token.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group McpCommerce
 * @group RestApi
 * @group TokenCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class TokenCest
{
    /**
     * US3-AC2 / mandatory scenario 9: the exchange returns an opaque MCP token, and neither the shop
     * access token nor a refresh token appears anywhere in the response.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function codeExchangeReturnsMcpTokenAndNoShopToken(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($clientIdentifier, $I->createCodeChallenge($codeVerifier));

        // Act
        $I->sendTokenRequest($clientIdentifier, $code, $codeVerifier);

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);

        $token = $I->grabResponseJson();

        $I->assertSame('Bearer', $token['token_type'] ?? null);
        $I->assertSame(28800, $token['expires_in'] ?? null);
        $I->assertSame('customer', $token['scope'] ?? null);

        $accessToken = (string)($token['access_token'] ?? '');
        $I->assertNotSame('', $accessToken);

        // The MCP credential is opaque: a shop JWT would start with `eyJ` and carry two dots.
        $I->assertStringStartsNotWith('eyJ', $accessToken);
        $I->assertStringNotContainsString('.', $accessToken);

        // Mandatory scenario 9, asserted on the raw body so no nesting can hide a leak.
        $I->assertArrayNotHasKey('refresh_token', $token);
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US3-AC3 / mandatory scenario 5: a mismatched PKCE verifier blocks token issuance. Without this,
     * an intercepted authorization code would be redeemable by the interceptor.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function pkceVerifierMismatchIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($clientIdentifier, $I->createCodeChallenge($codeVerifier));

        // Act — redeem with a different, valid-looking verifier.
        $I->sendTokenRequest($clientIdentifier, $code, $I->createCodeVerifier());

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertSame('invalid_grant', $I->grabResponseValue('error'));
        $I->assertNull($I->grabResponseValue('access_token'));
        $I->dontSeeResponseContainsShopToken();
    }

    /**
     * US3-AC4 / mandatory scenario 4: an authorization code cannot be replayed. The second exchange
     * of the same code fails even though the verifier is still correct.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function authorizationCodeCannotBeReplayed(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($clientIdentifier, $I->createCodeChallenge($codeVerifier));

        $I->sendTokenRequest($clientIdentifier, $code, $codeVerifier);
        $I->seeResponseCodeIs(HttpCode::OK);
        $firstAccessToken = (string)$I->grabResponseValue('access_token');

        // Act — replay the very same code.
        $I->sendTokenRequest($clientIdentifier, $code, $codeVerifier);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertSame('invalid_grant', $I->grabResponseValue('error'));
        $I->assertNull($I->grabResponseValue('access_token'));

        // The first token stays valid — replay protection must not revoke a legitimately issued token.
        $I->sendMcpRequest('tools/list', null, $firstAccessToken);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * OAuth 2.1 requires `redirect_uri` on the authorization-code grant so redemption is bound to the
     * redirect URI the code was issued for. Omitting it must be REJECTED, never treated as "no
     * binding to check" — that shape (optional means unchecked) previously let the check be skipped
     * entirely, so a code intercepted via one redirect URI could be redeemed claiming another.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function omittedRedirectUriIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($clientIdentifier, $I->createCodeChallenge($codeVerifier));

        // Act — a payload with every other parameter present, but no redirect_uri at all.
        $I->sendRawTokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientIdentifier,
            'code_verifier' => $codeVerifier,
        ]);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertNull($I->grabResponseValue('access_token'), 'No token may be issued without redirect_uri.');
    }

    /**
     * A redirect URI that does not match the one the code was issued for must be refused.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function mismatchedRedirectUriIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $clientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($clientIdentifier, $I->createCodeChallenge($codeVerifier));

        // Act
        $I->sendRawTokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientIdentifier,
            'code_verifier' => $codeVerifier,
            'redirect_uri' => 'http://localhost:9999/attacker-controlled',
        ]);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertSame('invalid_grant', $I->grabResponseValue('error'));
        $I->assertNull($I->grabResponseValue('access_token'));
    }

    /**
     * A code issued to one client must not be redeemable by another. Belt-and-braces beside the PKCE
     * check, since a public client's identifier is not a secret.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function codeIssuedToOneClientIsNotRedeemableByAnother(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $codeVerifier = $I->createCodeVerifier();
        $issuingClientIdentifier = $I->haveRegisteredClientIdentifier();
        $code = $I->haveAuthorizationCode($issuingClientIdentifier, $I->createCodeChallenge($codeVerifier));
        $otherClientIdentifier = $I->haveRegisteredClientIdentifier();

        // Act
        $I->sendTokenRequest($otherClientIdentifier, $code, $codeVerifier);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertNull($I->grabResponseValue('access_token'));
    }

    /**
     * An unknown code must never mint a token.
     *
     * @param \PyzTest\Glue\McpCommerce\McpCommerceRestApiTester $I
     *
     * @return void
     */
    public function unknownAuthorizationCodeIsRejected(McpCommerceRestApiTester $I): void
    {
        // Arrange
        $clientIdentifier = $I->haveRegisteredClientIdentifier();

        // Act
        $I->sendTokenRequest($clientIdentifier, 'this-code-was-never-issued', $I->createCodeVerifier());

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->assertNull($I->grabResponseValue('access_token'));
        $I->dontSeeResponseContainsShopToken();
    }
}
