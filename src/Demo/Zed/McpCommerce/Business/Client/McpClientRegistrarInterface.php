<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Client;

use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;

interface McpClientRegistrarInterface
{
    /**
     * Specification:
     * - Registers a public OAuth client for an MCP client and returns the generated client identifier.
     * - Rejects a request that carries no redirect URI, naming `redirect_uris` as the invalid field.
     * - Rejects a redirect URI that matches none of the configured allow-list patterns.
     * - Persists nothing when the request is rejected.
     *
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function register(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer;
}
