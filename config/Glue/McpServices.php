<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

use Demo\Client\McpCommerce\McpCommerceClient;
use Demo\Client\McpCommerce\McpCommerceClientInterface;
use Demo\Glue\McpCommerce\Controller\AuthorizeController;
use Demo\Glue\McpCommerce\Controller\McpController;
use Demo\Glue\McpCommerce\Controller\MetadataController;
use Demo\Glue\McpCommerce\Controller\RegistrationController;
use Demo\Glue\McpCommerce\Controller\TokenController;
use Demo\Glue\McpCommerce\EventSubscriber\McpTokenRequestSubscriber;
use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestInvoker;
use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestInvokerInterface;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcRequestParser;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcResponder;
use Demo\Glue\McpCommerce\Logger\McpAuthorizationAuditLogger;
use Demo\Glue\McpCommerce\Logger\McpAuthorizationAuditLoggerInterface;
use Demo\Glue\McpCommerce\McpCommerceConfig;
use Demo\Glue\McpCommerce\Renderer\ConsentScreenRenderer;
use Demo\Glue\McpCommerce\Renderer\ConsentScreenRendererInterface;
use Demo\Glue\McpCommerce\Security\McpCustomerIdentityReader;
use Demo\Glue\McpCommerce\Security\McpCustomerIdentityReaderInterface;
use Demo\Glue\McpCommerce\Security\McpOauthAuthenticator;
use Demo\Glue\McpCommerce\Tool\AddToCartTool;
use Demo\Glue\McpCommerce\Tool\CheckoutTool;
use Demo\Glue\McpCommerce\Tool\OrderListTool;
use Demo\Glue\McpCommerce\Tool\ProductDetailTool;
use Demo\Glue\McpCommerce\Tool\ProductSearchTool;
use Demo\Glue\McpCommerce\Tool\ToolRegistry;
use Demo\Glue\McpCommerce\Tool\ToolRegistryInterface;
use Demo\Shared\McpCommerce\McpCommerceConstants;
use Spryker\ApiPlatform\Security\OauthAuthenticator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Service wiring for the MCP Commerce Server. Auto-imported by the Spryker kernel through the
 * `{services,ApplicationServices,*Services}.php` glob, so `ApplicationServices.php` stays untouched.
 */
return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    // The Storefront API application has NO database connection (the container is started without
    // SPRYKER_DB_* credentials), so the McpCommerce Zed facade cannot be called in-process here: any
    // Propel query fails with "No connection defined for database zed". Every authorization store
    // operation therefore goes through the Client, which forwards to the Zed gateway over ZedRequest.
    //
    // The Client interface also has to be bound explicitly: `SprykerDefaultsPass` only auto-registers
    // Client interfaces for the CORE namespaces, so for a project namespace such as `Demo` the
    // interface never lands in the container on its own.
    $services->set(McpCommerceClient::class);
    $services->alias(McpCommerceClientInterface::class, McpCommerceClient::class);

    $services->set(McpCommerceConfig::class);
    $services->set(JsonRpcRequestParser::class);
    $services->set(JsonRpcResponder::class);

    $services->set(StorefrontSubRequestInvoker::class)
        ->arg('$httpKernel', new Symfony\Component\DependencyInjection\Reference('http_kernel'));
    $services->alias(StorefrontSubRequestInvokerInterface::class, StorefrontSubRequestInvoker::class);

    $services->set(McpAuthorizationAuditLogger::class);
    $services->alias(McpAuthorizationAuditLoggerInterface::class, McpAuthorizationAuditLogger::class);

    $services->set(ConsentScreenRenderer::class);
    $services->alias(ConsentScreenRendererInterface::class, ConsentScreenRenderer::class);

    $services->set(McpCustomerIdentityReader::class);
    $services->alias(McpCustomerIdentityReaderInterface::class, McpCustomerIdentityReader::class);

    // The five MCP commerce tools. Each one reaches the shop only through the sub-request invoker
    // against an existing Storefront resource, so no cart, checkout or catalog logic is duplicated.
    // The registry receives them as an iterable, which keeps the advertised tool surface defined here
    // in one place and keeps `new` out of the registry itself.
    $services->set(ProductSearchTool::class);
    $services->set(ProductDetailTool::class);
    $services->set(AddToCartTool::class);
    $services->set(CheckoutTool::class);
    $services->set(OrderListTool::class);

    $services->set(ToolRegistry::class)
        ->arg('$tools', [
            service(ProductSearchTool::class),
            service(ProductDetailTool::class),
            service(AddToCartTool::class),
            service(CheckoutTool::class),
            service(OrderListTool::class),
        ]);
    $services->alias(ToolRegistryInterface::class, ToolRegistry::class);

    $services->set(McpController::class)
        ->tag('controller.service_arguments');

    $services->set(MetadataController::class)
        ->tag('controller.service_arguments');

    $services->set(RegistrationController::class)
        ->tag('controller.service_arguments');

    $services->set(AuthorizeController::class)
        ->tag('controller.service_arguments');

    $services->set(TokenController::class)
        ->tag('controller.service_arguments');

    // The Spryker controller resolver only looks a controller up in the container when the
    // `_controller` string contains a `.` or a `:`; a plain FQCN is rejected outright. Aliasing the
    // controller under a dotted service id makes the resolver return the invokable object itself,
    // which also keeps the Glue REST controller listener from wrapping it as a legacy
    // `AbstractController` action pair.
    $services->alias(McpCommerceConstants::SERVICE_MCP_CONTROLLER, McpController::class)
        ->public();
    $services->alias(McpCommerceConstants::SERVICE_METADATA_CONTROLLER, MetadataController::class)
        ->public();
    $services->alias(McpCommerceConstants::SERVICE_REGISTRATION_CONTROLLER, RegistrationController::class)
        ->public();
    $services->alias(McpCommerceConstants::SERVICE_AUTHORIZE_CONTROLLER, AuthorizeController::class)
        ->public();
    $services->alias(McpCommerceConstants::SERVICE_TOKEN_CONTROLLER, TokenController::class)
        ->public();

    // `POST /token` is already claimed by the Storefront API from a `KernelEvents::REQUEST` listener
    // running above the router, so the MCP token route can never be reached on its own. This
    // subscriber runs one priority higher and answers only `grant_type=authorization_code`; setting a
    // response stops propagation, so every other grant type still reaches the core subscriber.
    $services->set(McpTokenRequestSubscriber::class)
        ->tag('kernel.event_subscriber');

    // Replace the core firewall authenticator so it steps aside for the MCP-owned paths, which
    // validate their own credentials and must emit their own `WWW-Authenticate` discovery pointer.
    $services->set(OauthAuthenticator::class, McpOauthAuthenticator::class);
};
