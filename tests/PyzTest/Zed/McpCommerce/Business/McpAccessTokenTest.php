<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\McpCommerce\Business;

use Codeception\Test\Unit;
use Demo\Zed\McpCommerce\Business\AccessToken\McpAccessTokenValidator;
use PyzTest\Zed\McpCommerce\McpCommerceBusinessTester;

/**
 * Locks down the PRD §5 MCP access-token rules: an issued token resolves the customer identity, a
 * revoked token stops resolving it, and no shop access or refresh token is ever stored alongside it
 * (PRD Goal 3 / mandatory scenario 9, asserted here at the persistence boundary).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group McpCommerce
 * @group Business
 * @group McpAccessTokenTest
 * Add your own group annotations below this line
 */
class McpAccessTokenTest extends Unit
{
    protected McpCommerceBusinessTester $tester;

    /**
     * PRD §5: an MCP access token lives 8 hours and is an opaque identifier, never a shop JWT.
     *
     * @return void
     */
    public function testIssueAccessTokenReturnsOpaqueTokenWithEightHourLifetime(): void
    {
        // Arrange
        $mcpIdentityTransfer = $this->tester->createIdentityTransfer($this->tester->haveClientIdentifier());

        // Act
        $mcpAccessTokenTransfer = $this->tester->getFacade()->issueAccessToken($mcpIdentityTransfer);

        // Assert
        $identifier = (string)$mcpAccessTokenTransfer->getIdentifier();
        $this->assertNotEmpty($identifier);
        $this->assertSame(28800, $mcpAccessTokenTransfer->getExpiresIn());

        // A shop JWT is a three-part dot-separated token starting with the `eyJ` header prefix.
        $this->assertStringStartsNotWith('eyJ', $identifier);
        $this->assertStringNotContainsString('.', $identifier);
    }

    /**
     * The full lifecycle in one test: issue -> validate (valid) -> revoke -> validate (refused).
     * This is the persistence-side counterpart of mandatory scenario 3.
     *
     * @return void
     */
    public function testAccessTokenIsValidUntilRevokedAndRefusedAfterwards(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveClientIdentifier();
        $mcpAccessTokenTransfer = $this->tester->getFacade()->issueAccessToken(
            $this->tester->createIdentityTransfer($clientIdentifier),
        );
        $identifier = (string)$mcpAccessTokenTransfer->getIdentifier();

        // Act
        $validationBeforeRevocationTransfer = $this->tester->getFacade()->validateAccessToken($identifier);
        $isRevoked = $this->tester->getFacade()->revokeAccessToken($identifier);
        $validationAfterRevocationTransfer = $this->tester->getFacade()->validateAccessToken($identifier);

        // Assert
        $this->assertTrue((bool)$validationBeforeRevocationTransfer->getIsValid());
        $this->assertSame(
            McpCommerceBusinessTester::CUSTOMER_REFERENCE,
            $validationBeforeRevocationTransfer->getMcpIdentityOrFail()->getCustomerReference(),
        );

        $this->assertTrue($isRevoked);

        $this->assertFalse((bool)$validationAfterRevocationTransfer->getIsValid());
        $this->assertSame(
            McpAccessTokenValidator::ERROR_CODE_INVALID_TOKEN,
            $validationAfterRevocationTransfer->getErrorCode(),
        );
        $this->assertNull($validationAfterRevocationTransfer->getMcpIdentity());
    }

    /**
     * An unknown token identifier must never resolve an identity.
     *
     * @return void
     */
    public function testValidateAccessTokenRefusesUnknownToken(): void
    {
        // Act
        $mcpAccessTokenValidationResponseTransfer = $this->tester->getFacade()->validateAccessToken(
            'this-token-was-never-issued',
        );

        // Assert
        $this->assertFalse((bool)$mcpAccessTokenValidationResponseTransfer->getIsValid());
        $this->assertSame(
            McpAccessTokenValidator::ERROR_CODE_INVALID_TOKEN,
            $mcpAccessTokenValidationResponseTransfer->getErrorCode(),
        );
        $this->assertNull($mcpAccessTokenValidationResponseTransfer->getMcpIdentity());
    }

    /**
     * A blank credential must be refused rather than treated as "no filter", which would otherwise
     * leak the first stored token. Guards the empty-customer-reference class of defect described in
     * the WP1 findings.
     *
     * @return void
     */
    public function testValidateAccessTokenRefusesBlankToken(): void
    {
        // Act
        $mcpAccessTokenValidationResponseTransfer = $this->tester->getFacade()->validateAccessToken('');

        // Assert
        $this->assertFalse((bool)$mcpAccessTokenValidationResponseTransfer->getIsValid());
        $this->assertNull($mcpAccessTokenValidationResponseTransfer->getMcpIdentity());
    }

    /**
     * Revoking a token that does not exist must not report success.
     *
     * @return void
     */
    public function testRevokeAccessTokenReportsFailureForUnknownToken(): void
    {
        // Act
        $isRevoked = $this->tester->getFacade()->revokeAccessToken('this-token-was-never-issued');

        // Assert
        $this->assertFalse($isRevoked);
    }

    /**
     * PRD Goal 3 / mandatory scenario 9, asserted structurally: the MCP token transfer carries only
     * identity claims. If a shop access or refresh token were ever added to the persistence contract,
     * this test fails.
     *
     * @return void
     */
    public function testIssuedAccessTokenTransferCarriesNoShopTokenField(): void
    {
        // Arrange
        $mcpAccessTokenTransfer = $this->tester->getFacade()->issueAccessToken(
            $this->tester->createIdentityTransfer($this->tester->haveClientIdentifier()),
        );

        // Act
        $serializedTransfer = json_encode($mcpAccessTokenTransfer->toArray(true, true));

        // Assert
        $this->assertIsString($serializedTransfer);
        $this->assertStringNotContainsStringIgnoringCase('refresh_token', $serializedTransfer);
        $this->assertStringNotContainsStringIgnoringCase('refreshtoken', $serializedTransfer);
        $this->assertStringNotContainsString('eyJ', $serializedTransfer);
    }
}
