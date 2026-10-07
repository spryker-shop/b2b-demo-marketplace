<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AccessToken;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

interface McpAccessTokenWriterInterface
{
    /**
     * Specification:
     * - Issues an opaque MCP access token bound to the given customer identity claims.
     *
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issue(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer;

    /**
     * Specification:
     * - Revokes the MCP access token with the given identifier.
     * - Returns true when a non-revoked token was revoked by this call.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function revoke(string $identifier): bool;
}
