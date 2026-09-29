<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AuthorizationCode;

use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;

interface McpAuthorizationCodeWriterInterface
{
    /**
     * Specification:
     * - Issues a single-use authorization code for the given client and customer identity.
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issue(McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer): McpAuthorizationCodeTransfer;
}
