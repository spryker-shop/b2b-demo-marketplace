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

interface McpCommerceRepositoryInterface
{
    /**
     * Specification:
     * - Returns the stored MCP authorization code matching the given opaque code.
     * - Returns null when no code matches.
     *
     * @param string $code
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer|null
     */
    public function findMcpAuthorizationCodeByCode(string $code): ?McpAuthorizationCodeTransfer;

    /**
     * Specification:
     * - Returns the stored MCP access token matching the given opaque identifier.
     * - Returns null when no token matches.
     *
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer|null
     */
    public function findMcpAccessTokenByIdentifier(string $identifier): ?McpAccessTokenTransfer;

    /**
     * Specification:
     * - Returns the registered OAuth client matching the given opaque client identifier.
     * - Returns null when no client matches.
     *
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer|null
     */
    public function findMcpClientByIdentifier(string $identifier): ?McpClientTransfer;
}
