<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class McpCommerceConfig extends AbstractBundleConfig
{
    /**
     * @var int
     */
    protected const AUTHORIZATION_CODE_TTL_SECONDS = 60;

    /**
     * @var int
     */
    protected const ACCESS_TOKEN_TTL_SECONDS = 28800;

    /**
     * @var int<1, max>
     */
    protected const RANDOM_IDENTIFIER_BYTE_LENGTH = 32;

    /**
     * @var string
     */
    protected const CODE_CHALLENGE_METHOD_S256 = 'S256';

    /**
     * @var string
     */
    protected const DEFAULT_SCOPES = 'customer';

    /**
     * @var string
     */
    protected const CLIENT_IDENTIFIER_PREFIX = 'mcp-';

    /**
     * @var array<string>
     */
    protected const ALLOWED_REDIRECT_URI_PATTERNS = [
        '#^https://([a-z0-9\\-]+\\.)*anthropic\\.com(/|$)#i',
        '#^https://([a-z0-9\\-]+\\.)*claude\\.ai(/|$)#i',
        '#^https://([a-z0-9\\-]+\\.)*openai\\.com(/|$)#i',
        '#^https://([a-z0-9\\-]+\\.)*chatgpt\\.com(/|$)#i',
        '#^https://([a-z0-9\\-]+\\.)*spryker\\.local(/|$)#i',
        '#^http://localhost(:\\d+)?(/|$)#i',
        '#^http://127\\.0\\.0\\.1(:\\d+)?(/|$)#i',
    ];

    /**
     * Specification:
     * - Returns the lifetime of an MCP authorization code in seconds.
     * - Must never exceed 60 seconds (PRD non-functional security requirements).
     *
     * @api
     *
     * @return int
     */
    public function getAuthorizationCodeTtlSeconds(): int
    {
        return static::AUTHORIZATION_CODE_TTL_SECONDS;
    }

    /**
     * Specification:
     * - Returns the lifetime of an MCP access token in seconds.
     * - Must never exceed 8 hours (PRD non-functional security requirements).
     *
     * @api
     *
     * @return int
     */
    public function getAccessTokenTtlSeconds(): int
    {
        return static::ACCESS_TOKEN_TTL_SECONDS;
    }

    /**
     * Specification:
     * - Returns the number of random bytes used to build opaque codes and tokens.
     *
     * @api
     *
     * @return int<1, max>
     */
    public function getRandomIdentifierByteLength(): int
    {
        return static::RANDOM_IDENTIFIER_BYTE_LENGTH;
    }

    /**
     * Specification:
     * - Returns the only accepted PKCE code challenge method.
     * - The `plain` method is deliberately not supported.
     *
     * @api
     *
     * @return string
     */
    public function getSupportedCodeChallengeMethod(): string
    {
        return static::CODE_CHALLENGE_METHOD_S256;
    }

    /**
     * Specification:
     * - Returns the space separated scope list granted to an MCP session by default.
     *
     * @api
     *
     * @return string
     */
    public function getDefaultScopes(): string
    {
        return static::DEFAULT_SCOPES;
    }

    /**
     * Specification:
     * - Returns the regular expression patterns a Dynamic Client Registration redirect URI must match.
     * - Registration is rejected for any redirect URI that matches none of these patterns.
     *
     * @api
     *
     * @return array<string>
     */
    public function getAllowedRedirectUriPatterns(): array
    {
        return static::ALLOWED_REDIRECT_URI_PATTERNS;
    }

    /**
     * Specification:
     * - Returns the prefix prepended to a generated MCP OAuth client identifier.
     *
     * @api
     *
     * @return string
     */
    public function getClientIdentifierPrefix(): string
    {
        return static::CLIENT_IDENTIFIER_PREFIX;
    }
}
