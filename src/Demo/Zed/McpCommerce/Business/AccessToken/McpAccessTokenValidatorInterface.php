<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AccessToken;

use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;

interface McpAccessTokenValidatorInterface
{
    /**
     * Specification:
     * - Validates an opaque MCP access token and resolves the customer identity claims.
     * - Rejects unknown, expired and revoked tokens.
     *
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validate(string $identifier): McpAccessTokenValidationResponseTransfer;
}
