<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Spryker\Glue\Kernel\AbstractBundleConfig;

class McpCommerceConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    protected const CHECKOUT_PAYMENT_METHOD_NAME = 'Invoice';

    /**
     * @var string
     */
    protected const CHECKOUT_PAYMENT_PROVIDER_NAME = 'DummyPayment';

    /**
     * @var int
     */
    protected const CHECKOUT_ID_SHIPMENT_METHOD = 1;

    /**
     * @var int
     */
    protected const DISCOVERY_METADATA_CACHE_TTL_SECONDS = 3600;

    /**
     * @var array<string>
     */
    protected const SUPPORTED_GRANT_TYPES = ['authorization_code'];

    /**
     * @var array<string>
     */
    protected const SUPPORTED_CODE_CHALLENGE_METHODS = ['S256'];

    /**
     * @var array<string>
     */
    protected const SUPPORTED_RESPONSE_TYPES = ['code'];

    /**
     * @var array<string>
     */
    protected const SUPPORTED_TOKEN_ENDPOINT_AUTH_METHODS = ['none'];

    /**
     * @var array<string>
     */
    protected const SUPPORTED_SCOPES = ['customer'];

    /**
     * Specification:
     * - Returns true when the MCP Commerce Server feature is enabled in Configuration Management.
     * - Fails closed: an unconfigured environment must not expose the MCP commerce surface.
     *
     * @api
     */
    public function isEnabled(): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig(McpCommerceConstants::CONFIGURATION_KEY_IS_ENABLED, false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Specification:
     * - Returns the negotiated MCP protocol revision advertised by the `initialize` method.
     *
     * @api
     */
    public function getProtocolVersion(): string
    {
        return McpCommerceConstants::PROTOCOL_VERSION;
    }

    /**
     * Specification:
     * - Returns the public server name advertised by the `initialize` method.
     *
     * @api
     */
    public function getServerName(): string
    {
        return McpCommerceConstants::SERVER_NAME;
    }

    /**
     * Specification:
     * - Returns the public server version advertised by the `initialize` method.
     *
     * @api
     */
    public function getServerVersion(): string
    {
        return McpCommerceConstants::SERVER_VERSION;
    }

    /**
     * Specification:
     * - Returns the payment method name used when placing an order through the checkout tool.
     * - MCP checkout deliberately exposes no payment selection, so a single configured method is used.
     *
     * @api
     */
    public function getCheckoutPaymentMethodName(): string
    {
        return static::CHECKOUT_PAYMENT_METHOD_NAME;
    }

    /**
     * Specification:
     * - Returns the payment provider name used when placing an order through the checkout tool.
     *
     * @api
     */
    public function getCheckoutPaymentProviderName(): string
    {
        return static::CHECKOUT_PAYMENT_PROVIDER_NAME;
    }

    /**
     * Specification:
     * - Returns the shipment method id used when placing an order through the checkout tool.
     * - MCP checkout deliberately exposes no shipment selection, so a single configured method is used.
     *
     * @api
     */
    public function getCheckoutIdShipmentMethod(): int
    {
        return static::CHECKOUT_ID_SHIPMENT_METHOD;
    }

    /**
     * Specification:
     * - Returns the request paths owned by the MCP Commerce Server that must not be handled by the
     *   core OAuth firewall authenticator.
     *
     * @api
     *
     * @return array<string>
     */
    public function getUnauthenticatedPaths(): array
    {
        return [
            McpCommerceConstants::PATH_MCP,
            McpCommerceConstants::PATH_TOKEN,
            McpCommerceConstants::PATH_REGISTER,
            McpCommerceConstants::PATH_AUTHORIZE,
            McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA,
            McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA,
        ];
    }

    /**
     * Specification:
     * - Returns how long a discovery metadata document may be cached, in seconds.
     *
     * @api
     */
    public function getDiscoveryMetadataCacheTtlSeconds(): int
    {
        return static::DISCOVERY_METADATA_CACHE_TTL_SECONDS;
    }

    /**
     * Specification:
     * - Returns the OAuth grant types advertised by the authorization server metadata document.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedGrantTypes(): array
    {
        return static::SUPPORTED_GRANT_TYPES;
    }

    /**
     * Specification:
     * - Returns the PKCE code challenge methods advertised by the authorization server metadata.
     * - The `plain` method is deliberately never advertised nor accepted.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedCodeChallengeMethods(): array
    {
        return static::SUPPORTED_CODE_CHALLENGE_METHODS;
    }

    /**
     * Specification:
     * - Returns the OAuth response types advertised by the authorization server metadata document.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedResponseTypes(): array
    {
        return static::SUPPORTED_RESPONSE_TYPES;
    }

    /**
     * Specification:
     * - Returns the token endpoint client authentication methods advertised by the metadata document.
     * - Only `none` is supported because every MCP client registers as a public PKCE client.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedTokenEndpointAuthMethods(): array
    {
        return static::SUPPORTED_TOKEN_ENDPOINT_AUTH_METHODS;
    }

    /**
     * Specification:
     * - Returns the OAuth scopes advertised by the authorization server metadata document.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedScopes(): array
    {
        return static::SUPPORTED_SCOPES;
    }
}
