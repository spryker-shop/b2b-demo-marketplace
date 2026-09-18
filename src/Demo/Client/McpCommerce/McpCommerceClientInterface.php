<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\McpCommerce;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientRequestTransfer;
use Generated\Shared\Transfer\McpClientResponseTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

interface McpCommerceClientInterface
{
    /**
     * Specification:
     * - Registers a public OAuth client for an MCP client through Dynamic Client Registration.
     * - Makes a call to Zed.
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
     * - Returns the registered MCP OAuth client matching the requested client identifier.
     * - Returns a response transfer whose `mcpClient` is null when no client is registered.
     * - Makes a call to Zed.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpClientRequestTransfer $mcpClientRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientResponseTransfer
     */
    public function findClient(McpClientRequestTransfer $mcpClientRequestTransfer): McpClientResponseTransfer;

    /**
     * Specification:
     * - Issues a single-use MCP authorization code for the given OAuth client and customer identity.
     * - Makes a call to Zed.
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
     * - Redeems an MCP authorization code exactly once, verifying PKCE with the S256 method only.
     * - Makes a call to Zed.
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
     * - Makes a call to Zed.
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
     * - Validates an opaque MCP access token identifier, rejecting unknown, revoked and expired tokens.
     * - Makes a call to Zed.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenValidationResponseTransfer;
}
