<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\McpCommerce\Business;

use Codeception\Test\Unit;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeRedeemer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use PyzTest\Zed\McpCommerce\McpCommerceBusinessTester;

/**
 * Locks down the PRD §5 authorization-code rules: a code is single-use, short-lived, bound to the
 * issuing client and redirect URI, and only redeemable with a matching S256 PKCE verifier.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group McpCommerce
 * @group Business
 * @group McpAuthorizationCodeTest
 * Add your own group annotations below this line
 */
class McpAuthorizationCodeTest extends Unit
{
    protected McpCommerceBusinessTester $tester;

    /**
     * PRD §5: the authorization code TTL never exceeds 60 seconds, and the issued code is opaque and
     * generated server-side rather than taken from the caller.
     *
     * @return void
     */
    public function testIssueAuthorizationCodeReturnsOpaqueSingleUseCodeWithShortTtl(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $clientIdentifier = $this->tester->haveClientIdentifier();

        // Act
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $this->tester->createCodeChallenge($codeVerifier),
        );

        // Assert
        $this->assertNotEmpty($mcpAuthorizationCodeTransfer->getCode());
        $this->assertFalse((bool)$mcpAuthorizationCodeTransfer->getIsUsed());
        $this->assertSame($clientIdentifier, $mcpAuthorizationCodeTransfer->getClientIdentifier());

        $expiresAt = (int)strtotime((string)$mcpAuthorizationCodeTransfer->getExpiresAt());
        $this->assertGreaterThan(time(), $expiresAt);
        $this->assertLessThanOrEqual(time() + 60, $expiresAt);
    }

    /**
     * PRD §5 / mandatory scenario 5: a mismatched PKCE verifier must block token issuance.
     *
     * @return void
     */
    public function testRedeemRejectsWrongPkceCodeVerifier(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $this->tester->createCodeChallenge($this->tester->createCodeVerifier()),
        );

        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode($mcpAuthorizationCodeTransfer->getCode())
                ->setClientIdentifier($clientIdentifier)
                ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
                ->setCodeVerifier($this->tester->createCodeVerifier()),
        );

        // Assert
        $this->assertFalse((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpAuthorizationCodeRedeemer::ERROR_CODE_INVALID_GRANT,
            $mcpAuthorizationCodeRedemptionResponseTransfer->getErrorCode(),
        );
        $this->assertNull($mcpAuthorizationCodeRedemptionResponseTransfer->getMcpIdentity());
    }

    /**
     * The happy path: the correct verifier resolves the customer identity claims.
     *
     * @return void
     */
    public function testRedeemAcceptsCorrectPkceCodeVerifierAndResolvesIdentity(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $this->tester->createCodeChallenge($codeVerifier),
        );

        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode($mcpAuthorizationCodeTransfer->getCode())
                ->setClientIdentifier($clientIdentifier)
                ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
                ->setCodeVerifier($codeVerifier),
        );

        // Assert
        $this->assertTrue((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpCommerceBusinessTester::CUSTOMER_REFERENCE,
            $mcpAuthorizationCodeRedemptionResponseTransfer->getMcpIdentityOrFail()->getCustomerReference(),
        );
    }

    /**
     * PRD §5 / mandatory scenario 4: an authorization code cannot be replayed. The second redemption
     * of the same code must fail even though the verifier is still correct.
     *
     * @return void
     */
    public function testRedeemRejectsReplayOfAlreadyRedeemedCode(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $this->tester->createCodeChallenge($codeVerifier),
        );
        $mcpAuthorizationCodeRedemptionRequestTransfer = (new McpAuthorizationCodeRedemptionRequestTransfer())
            ->setCode($mcpAuthorizationCodeTransfer->getCode())
            ->setClientIdentifier($clientIdentifier)
            ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
            ->setCodeVerifier($codeVerifier);

        $firstResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            $mcpAuthorizationCodeRedemptionRequestTransfer,
        );

        // Act
        $secondResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            $mcpAuthorizationCodeRedemptionRequestTransfer,
        );

        // Assert
        $this->assertTrue((bool)$firstResponseTransfer->getIsSuccessful());
        $this->assertFalse((bool)$secondResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpAuthorizationCodeRedeemer::ERROR_CODE_INVALID_GRANT,
            $secondResponseTransfer->getErrorCode(),
        );
    }

    /**
     * PRD §5: only the S256 code challenge method is supported. A code issued with the downgraded
     * `plain` method must never be redeemable, even when the verifier equals the challenge.
     *
     * @return void
     */
    public function testRedeemRejectsPlainCodeChallengeMethod(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $codeVerifier,
            McpCommerceBusinessTester::CODE_CHALLENGE_METHOD_PLAIN,
        );

        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode($mcpAuthorizationCodeTransfer->getCode())
                ->setClientIdentifier($clientIdentifier)
                ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
                ->setCodeVerifier($codeVerifier),
        );

        // Assert
        $this->assertFalse((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpAuthorizationCodeRedeemer::ERROR_CODE_INVALID_GRANT,
            $mcpAuthorizationCodeRedemptionResponseTransfer->getErrorCode(),
        );
    }

    /**
     * PRD §5: a code is bound to the client it was issued to.
     *
     * @return void
     */
    public function testRedeemRejectsCodeReplayedByAnotherClient(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $this->tester->haveClientIdentifier(),
            $this->tester->createCodeChallenge($codeVerifier),
        );

        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode($mcpAuthorizationCodeTransfer->getCode())
                ->setClientIdentifier($this->tester->haveClientIdentifier())
                ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
                ->setCodeVerifier($codeVerifier),
        );

        // Assert
        $this->assertFalse((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpAuthorizationCodeRedeemer::ERROR_CODE_INVALID_GRANT,
            $mcpAuthorizationCodeRedemptionResponseTransfer->getErrorCode(),
        );
    }

    /**
     * PRD §5: a code is bound to the redirect URI of the authorization request.
     *
     * @return void
     */
    public function testRedeemRejectsMismatchedRedirectUri(): void
    {
        // Arrange
        $codeVerifier = $this->tester->createCodeVerifier();
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAuthorizationCodeTransfer = $this->tester->haveAuthorizationCode(
            $clientIdentifier,
            $this->tester->createCodeChallenge($codeVerifier),
        );

        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode($mcpAuthorizationCodeTransfer->getCode())
                ->setClientIdentifier($clientIdentifier)
                ->setRedirectUri('http://localhost:8080/somewhere-else')
                ->setCodeVerifier($codeVerifier),
        );

        // Assert
        $this->assertFalse((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertSame(
            McpAuthorizationCodeRedeemer::ERROR_CODE_INVALID_GRANT,
            $mcpAuthorizationCodeRedemptionResponseTransfer->getErrorCode(),
        );
    }

    /**
     * An unknown code must never resolve an identity.
     *
     * @return void
     */
    public function testRedeemRejectsUnknownCode(): void
    {
        // Act
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->tester->getFacade()->redeemAuthorizationCode(
            (new McpAuthorizationCodeRedemptionRequestTransfer())
                ->setCode('this-code-was-never-issued')
                ->setClientIdentifier($this->tester->haveClientIdentifier())
                ->setRedirectUri(McpCommerceBusinessTester::REDIRECT_URI)
                ->setCodeVerifier($this->tester->createCodeVerifier()),
        );

        // Assert
        $this->assertFalse((bool)$mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful());
        $this->assertNull($mcpAuthorizationCodeRedemptionResponseTransfer->getMcpIdentity());
    }
}
