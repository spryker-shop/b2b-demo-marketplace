<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Shared\McpCommerce;

interface McpCommerceConstants
{
    /**
     * Configuration Management key for the MCP Commerce Server feature flag.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_IS_ENABLED = 'mcp_commerce:server:general:is_enabled';

    /**
     * MCP protocol revision negotiated by the `initialize` method.
     *
     * @api
     */
    public const string PROTOCOL_VERSION = '2025-06-18';

    /**
     * JSON-RPC protocol version used by every MCP envelope.
     *
     * @api
     */
    public const string JSON_RPC_VERSION = '2.0';

    /**
     * Public name of the MCP server reported by the `initialize` method.
     *
     * @api
     */
    public const string SERVER_NAME = 'spryker-mcp-commerce';

    /**
     * Public version of the MCP server reported by the `initialize` method.
     *
     * @api
     */
    public const string SERVER_VERSION = '1.0.0';

    /**
     * Container service id of the MCP JSON-RPC controller.
     *
     * Deliberately dotted: the Spryker controller resolver only performs a container lookup for a
     * `_controller` value that contains a `.` or a `:`, and returns the invokable object directly
     * in that case.
     *
     * @api
     */
    public const string SERVICE_MCP_CONTROLLER = 'demo.mcp_commerce.controller.mcp';

    /**
     * MCP protocol path serving the JSON-RPC transport.
     *
     * @api
     */
    public const string PATH_MCP = '/mcp';

    /**
     * OAuth token endpoint path owned by this feature.
     *
     * @api
     */
    public const string PATH_TOKEN = '/token';

    /**
     * OAuth dynamic client registration endpoint path owned by this feature.
     *
     * @api
     */
    public const string PATH_REGISTER = '/register';

    /**
     * OAuth authorization endpoint path owned by this feature.
     *
     * @api
     */
    public const string PATH_AUTHORIZE = '/authorize';

    /**
     * Path of the protected resource metadata document advertised in `WWW-Authenticate`.
     *
     * @api
     */
    public const string PATH_OAUTH_PROTECTED_RESOURCE_METADATA = '/.well-known/oauth-protected-resource';

    /**
     * Path of the authorization server metadata document.
     *
     * @api
     */
    public const string PATH_OAUTH_AUTHORIZATION_SERVER_METADATA = '/.well-known/oauth-authorization-server';

    /**
     * Container service id of the OAuth discovery metadata controller.
     *
     * Deliberately dotted, see {@link \Demo\Shared\McpCommerce\McpCommerceConstants::SERVICE_MCP_CONTROLLER}.
     *
     * @api
     */
    public const string SERVICE_METADATA_CONTROLLER = 'demo.mcp_commerce.controller.metadata';

    /**
     * Container service id of the Dynamic Client Registration controller.
     *
     * Deliberately dotted, see {@link \Demo\Shared\McpCommerce\McpCommerceConstants::SERVICE_MCP_CONTROLLER}.
     *
     * @api
     */
    public const string SERVICE_REGISTRATION_CONTROLLER = 'demo.mcp_commerce.controller.registration';

    /**
     * Container service id of the OAuth authorization controller.
     *
     * Deliberately dotted, see {@link \Demo\Shared\McpCommerce\McpCommerceConstants::SERVICE_MCP_CONTROLLER}.
     *
     * @api
     */
    public const string SERVICE_AUTHORIZE_CONTROLLER = 'demo.mcp_commerce.controller.authorize';

    /**
     * Container service id of the OAuth token controller.
     *
     * Deliberately dotted, see {@link \Demo\Shared\McpCommerce\McpCommerceConstants::SERVICE_MCP_CONTROLLER}.
     *
     * @api
     */
    public const string SERVICE_TOKEN_CONTROLLER = 'demo.mcp_commerce.controller.token';
}
