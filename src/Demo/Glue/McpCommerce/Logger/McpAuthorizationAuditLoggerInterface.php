<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Logger;

interface McpAuthorizationAuditLoggerInterface
{
    /**
     * Specification:
     * - Writes an MCP authorization event to the security audit log channel.
     * - Accepts only non-secret context values; credentials and tokens must never be passed in.
     *
     * @param string $message
     * @param array<string> $tags
     * @param array<string, scalar|null> $context
     *
     * @return void
     */
    public function logAuthorizationEvent(string $message, array $tags, array $context = []): void;
}
