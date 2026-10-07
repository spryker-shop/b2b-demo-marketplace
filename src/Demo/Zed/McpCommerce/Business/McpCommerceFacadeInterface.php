<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

interface McpCommerceFacadeInterface
{
    /**
     * Specification:
     * - Issues a single-use MCP authorization code for the given OAuth client and customer identity.
     * - Generates a cryptographically random opaque code, ignoring any code supplied by the caller.
     * - Sets the expiration to the configured authorization code TTL, which never exceeds 60 seconds.
     * - Requires `clientIdentifier`, `customerReference`, `idCustomer`, `codeChallenge`,
     *   `codeChallengeMethod` and `redirectUri` on the given transfer.
     * - Never persists or accepts a shop access or refresh token.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issueAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer;

    /**
     * Specification:
     * - Redeems an MCP authorization code exactly once and resolves the customer identity claims.
     * - Rejects an unknown, already redeemed or expired code.
     * - Rejects a client identifier or redirect URI that does not match the authorization request.
     * - Verifies PKCE with the S256 method only, the `plain` method is always rejected.
     * - Returns a response transfer carrying `isSuccessful`, and on failure `errorCode` + `errorMessage`.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeemAuthorizationCode(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer;

    /**
     * Specification:
     * - Issues an opaque MCP access token bound to the given customer identity claims.
     * - Sets the expiration to the configured access token TTL, which never exceeds 8 hours.
     * - Requires `clientIdentifier`, `customerReference` and `idCustomer` on the given transfer.
     * - Returns the token transfer including `expiresIn` in seconds.
     * - Never persists or returns a shop access or refresh token.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issueAccessToken(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer;

    /**
     * Specification:
     * - Validates an opaque MCP access token identifier.
     * - Rejects unknown, revoked and expired tokens.
     * - Returns the resolved customer identity claims when the token is valid.
     *
     * @api
     *
     * @param string $accessTokenIdentifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(string $accessTokenIdentifier): McpAccessTokenValidationResponseTransfer;

    /**
     * Specification:
     * - Revokes a single MCP access token without affecting the customer's other tokens or sessions.
     * - Returns true when a not yet revoked token was revoked by this call.
     *
     * @api
     *
     * @param string $accessTokenIdentifier
     *
     * @return bool
     */
    public function revokeAccessToken(string $accessTokenIdentifier): bool;

    /**
     * Specification:
     * - Deletes MCP authorization codes that expired before the current date and time.
     * - Returns the number of deleted rows.
     *
     * @api
     *
     * @return int
     */
    public function deleteExpiredAuthorizationCodes(): int;

    /**
     * Specification:
     * - Registers a public OAuth client for an MCP client through Dynamic Client Registration.
     * - Generates a unique opaque client identifier and stores the client as requiring PKCE.
     * - Returns `isSuccessful = false` with `errorCode`, `errorMessage` and `invalidField` when the
     *   request carries no redirect URI or a redirect URI outside the configured allow-list.
     * - Persists no client record when the request is rejected.
     * - Never issues or stores a client secret.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function registerClient(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer;

    /**
     * Specification:
     * - Returns the registered MCP OAuth client matching the given opaque client identifier.
     * - Returns null when no client is registered under that identifier.
     *
     * @api
     *
     * @param string $clientIdentifier
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer|null
     */
    public function findClientByIdentifier(string $clientIdentifier): ?McpClientTransfer;
}
