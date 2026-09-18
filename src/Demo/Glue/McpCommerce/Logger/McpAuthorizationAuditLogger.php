<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Logger;

use Generated\Shared\Transfer\AuditLoggerConfigCriteriaTransfer;
use Spryker\Shared\Log\AuditLoggerTrait;

/**
 * Writes MCP authorization events (client registration, code issuance, token issuance, token
 * rejection) to the `security` audit log channel, the same channel the platform's own OAuth
 * authenticator uses for failed logins.
 *
 * Only non-secret identifiers reach the log: shop access tokens, shop refresh tokens, MCP access
 * tokens, authorization codes, code verifiers and passwords are never accepted as context values by
 * any caller, which keeps the audit trail useful without turning it into a credential store.
 */
class McpAuthorizationAuditLogger implements McpAuthorizationAuditLoggerInterface
{
    use AuditLoggerTrait;

    /**
     * @uses \Spryker\Shared\Log\LogConfig::AUDIT_LOGGER_CHANNEL_NAME_SECURITY
     *
     * @var string
     */
    protected const AUDIT_LOGGER_CHANNEL_NAME_SECURITY = 'security';

    /**
     * @var string
     */
    protected const AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS = 'tags';

    /**
     * @var string
     */
    protected const AUDIT_LOGGER_TAG_MCP = 'mcp_commerce';

    /**
     * @param string $message
     * @param array<string> $tags
     * @param array<string, scalar|null> $context
     *
     * @return void
     */
    public function logAuthorizationEvent(string $message, array $tags, array $context = []): void
    {
        $this->getAuditLogger(
            (new AuditLoggerConfigCriteriaTransfer())->setChannelName(static::AUDIT_LOGGER_CHANNEL_NAME_SECURITY),
        )->info(
            $message,
            $context + [
                static::AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS => array_merge([static::AUDIT_LOGGER_TAG_MCP], $tags),
            ],
        );
    }
}
