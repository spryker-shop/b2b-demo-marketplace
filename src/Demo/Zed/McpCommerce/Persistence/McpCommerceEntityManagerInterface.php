<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Persistence;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientTransfer;

interface McpCommerceEntityManagerInterface
{
    /**
     * Specification:
     * - Persists a new MCP authorization code.
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function createMcpAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer;

    /**
     * Specification:
     * - Atomically marks the authorization code identified by the given code as used.
     * - Only affects rows that are not used yet, which makes redemption single-use.
     * - Returns the number of affected rows.
     *
     * @param string $code
     *
     * @return int
     */
    public function markMcpAuthorizationCodeAsUsed(string $code): int;

    /**
     * Specification:
     * - Persists a new MCP access token.
     *
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function createMcpAccessToken(McpAccessTokenTransfer $mcpAccessTokenTransfer): McpAccessTokenTransfer;

    /**
     * Specification:
     * - Marks the access token identified by the given identifier as revoked.
     * - Only affects rows that are not revoked yet.
     * - Returns the number of affected rows.
     *
     * @param string $identifier
     *
     * @return int
     */
    public function revokeMcpAccessToken(string $identifier): int;

    /**
     * Specification:
     * - Deletes authorization codes that expired before the given date time.
     * - Returns the number of deleted rows.
     *
     * @param string $expiresBefore
     *
     * @return int
     */
    public function deleteExpiredMcpAuthorizationCodes(string $expiresBefore): int;

    /**
     * Specification:
     * - Persists a new OAuth client registration for an MCP client.
     * - Stores the client as a public client, so no client secret is created.
     *
     * @param \Generated\Shared\Transfer\McpClientTransfer $mcpClientTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer
     */
    public function createMcpClient(McpClientTransfer $mcpClientTransfer): McpClientTransfer;
}
