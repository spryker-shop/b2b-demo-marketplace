<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Routes owned by the MCP Commerce Server. Kept in a dedicated file so the API Platform route
 * import in `routes/api_platform.php` stays untouched.
 */
return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('mcp_commerce_server', McpCommerceConstants::PATH_MCP)
        ->controller(McpCommerceConstants::SERVICE_MCP_CONTROLLER)
        ->methods(['POST']);

    $routingConfigurator
        ->add('mcp_commerce_oauth_authorization_server_metadata', McpCommerceConstants::PATH_OAUTH_AUTHORIZATION_SERVER_METADATA)
        ->controller(McpCommerceConstants::SERVICE_METADATA_CONTROLLER)
        ->methods(['GET']);

    $routingConfigurator
        ->add('mcp_commerce_oauth_protected_resource_metadata', McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA)
        ->controller(McpCommerceConstants::SERVICE_METADATA_CONTROLLER)
        ->methods(['GET']);

    $routingConfigurator
        ->add('mcp_commerce_oauth_registration', McpCommerceConstants::PATH_REGISTER)
        ->controller(McpCommerceConstants::SERVICE_REGISTRATION_CONTROLLER)
        ->methods(['POST']);

    $routingConfigurator
        ->add('mcp_commerce_oauth_authorize', McpCommerceConstants::PATH_AUTHORIZE)
        ->controller(McpCommerceConstants::SERVICE_AUTHORIZE_CONTROLLER)
        ->methods(['GET', 'POST']);

    $routingConfigurator
        ->add('mcp_commerce_oauth_token', McpCommerceConstants::PATH_TOKEN)
        ->controller(McpCommerceConstants::SERVICE_TOKEN_CONTROLLER)
        ->methods(['POST']);
};
