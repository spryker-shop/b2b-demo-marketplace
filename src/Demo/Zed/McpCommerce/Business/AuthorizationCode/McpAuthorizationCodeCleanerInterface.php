<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AuthorizationCode;

interface McpAuthorizationCodeCleanerInterface
{
    /**
     * Specification:
     * - Deletes authorization codes that already expired.
     *
     * @return int
     */
    public function deleteExpired(): int;
}
