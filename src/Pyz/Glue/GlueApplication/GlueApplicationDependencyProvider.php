<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\GlueApplication;

use Spryker\Glue\AgentAuthRestApi\Plugin\GlueApplication\AgentAccessTokenRestRequestValidatorPlugin;
use Spryker\Glue\AgentAuthRestApi\Plugin\GlueApplication\AgentAccessTokenRestUserFinderPlugin;
use Spryker\Glue\AgentAuthRestApi\Plugin\GlueApplication\AgentRestUserValidatorPlugin;
use Spryker\Glue\AuthRestApi\Plugin\FormatAuthenticationErrorResponseHeadersPlugin;
use Spryker\Glue\AuthRestApi\Plugin\GlueApplication\AccessTokenRestRequestValidatorPlugin;
use Spryker\Glue\AuthRestApi\Plugin\GlueApplication\FormattedControllerBeforeActionValidateAccessTokenPlugin;
use Spryker\Glue\AuthRestApi\Plugin\GlueApplication\SimultaneousAuthenticationRestRequestValidatorPlugin;
use Spryker\Glue\AuthRestApi\Plugin\RestUserFinderByAccessTokenPlugin;
use Spryker\Glue\CartsRestApi\Plugin\ControllerBeforeAction\SetAnonymousCustomerIdControllerBeforeActionPlugin;
use Spryker\Glue\CartsRestApi\Plugin\Validator\AnonymousCustomerUniqueIdValidatorPlugin;
use Spryker\Glue\CatalogSearchRestApi\Plugin\CatalogSearchRequestParametersIntegerRestRequestValidatorPlugin;
use Spryker\Glue\CompanyUsersRestApi\Plugin\GlueApplication\CompanyUserRestUserValidatorPlugin;
use Spryker\Glue\CustomerAccessRestApi\Plugin\GlueApplication\CustomerAccessFormatRequestPlugin;
use Spryker\Glue\CustomersRestApi\Plugin\SetCustomerBeforeActionPlugin;
use Spryker\Glue\EntityTagsRestApi\Plugin\GlueApplication\EntityTagFormatResponseHeadersPlugin;
use Spryker\Glue\EntityTagsRestApi\Plugin\GlueApplication\EntityTagRestRequestValidatorPlugin;
use Spryker\Glue\EventDispatcher\Plugin\Application\EventDispatcherApplicationPlugin;
use Spryker\Glue\GlueApplication\GlueApplicationDependencyProvider as SprykerGlueApplicationDependencyProvider;
use Spryker\Glue\GlueApplication\Plugin\Application\GlueApplicationApplicationPlugin;
use Spryker\Glue\GlueApplication\Plugin\GlueApplication\CorsValidateHttpRequestPlugin;
use Spryker\Glue\GlueApplication\Plugin\GlueApplication\HeadersValidateHttpRequestPlugin;
use Spryker\Glue\GlueApplication\Plugin\GlueApplication\PaginationParametersValidateHttpRequestPlugin;
use Spryker\Glue\GlueApplicationAuthorizationConnector\Plugin\GlueApplication\AuthorizationRestUserValidatorPlugin;
use Spryker\Glue\GlueApplicationAuthorizationConnector\Plugin\GlueApplication\AuthorizationRouterParameterExpanderPlugin;
use Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\BackendApiGlueApplicationBootstrapPlugin;
use Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\BackendRouterProviderPlugin;
use Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\ControllerConfigurationCacheCollectorPlugin as BackendControllerConfigurationCacheCollectorPlugin;
use Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\CustomRouteRoutesProviderPlugin as BackendCustomRouteRoutesProviderPlugin;
use Spryker\Glue\GlueBackendApiApplication\Plugin\GlueApplication\ResourcesProviderPlugin as BackendResourcesProviderPlugin;
use Spryker\Glue\GlueBackendApiApplicationAuthorizationConnector\Plugin\GlueApplication\IsProtectedTableColumnExpanderPlugin as BackendIsProtectedTableColumnExpanderPlugin;
use Spryker\Glue\GlueJsonApiConvention\Plugin\GlueApplication\JsonApiConventionPlugin;
use Spryker\Glue\Http\Plugin\Application\HttpApplicationPlugin;
use Spryker\Glue\Locale\Plugin\Application\LocaleApplicationPlugin;
use Spryker\Glue\MultiFactorAuth\Plugin\GlueApplication\RestApi\MultiFactorAuthRestUserValidatorPlugin;
use Spryker\Glue\ProductConfigurationsRestApi\Plugin\GlueApplication\CartItemProductConfigurationRestRequestValidatorPlugin;
use Spryker\Glue\ProductPricesRestApi\Plugin\GlueApplication\CurrencyParameterValidatorPlugin;
use Spryker\Glue\ProductPricesRestApi\Plugin\GlueApplication\PriceModeParameterValidatorPlugin;
use Spryker\Glue\ProductPricesRestApi\Plugin\GlueApplication\SetPriceModeBeforeActionPlugin;
use Spryker\Glue\RestRequestValidator\Plugin\ValidateRestRequestAttributesPlugin;
use Spryker\Glue\Router\Plugin\Application\RouterApplicationPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentControllerAfterActionPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerAgentRestRequestValidatorPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerCustomerControllerAfterActionPlugin;
use Spryker\Glue\SecurityBlockerRestApi\Plugin\GlueApplication\SecurityBlockerCustomerRestRequestValidatorPlugin;
use Spryker\Glue\Session\Plugin\Application\SessionApplicationPlugin;
use Spryker\Glue\StoresApi\Plugin\GlueStorefrontApiApplication\StoreApplicationPlugin;
use Spryker\Glue\WebProfiler\Plugin\Application\WebProfilerApplicationPlugin;

class GlueApplicationDependencyProvider extends SprykerGlueApplicationDependencyProvider
{
    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RestUserValidatorPluginInterface>
     */
    protected function getRestUserValidatorPlugins(): array
    {
        return [
            new CompanyUserRestUserValidatorPlugin(),
            new AgentRestUserValidatorPlugin(),
            new AuthorizationRestUserValidatorPlugin(),
            new MultiFactorAuthRestUserValidatorPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ValidateHttpRequestPluginInterface>
     */
    protected function getValidateHttpRequestPlugins(): array
    {
        return [
            new CorsValidateHttpRequestPlugin(),
            new PaginationParametersValidateHttpRequestPlugin(),
            new HeadersValidateHttpRequestPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\FormattedControllerBeforeActionPluginInterface>
     */
    protected function getFormattedControllerBeforeActionTerminatePlugins(): array
    {
        return [
            new FormattedControllerBeforeActionValidateAccessTokenPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\FormatRequestPluginInterface>
     */
    protected function getFormatRequestPlugins(): array
    {
        return [
            new CustomerAccessFormatRequestPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RestRequestValidatorPluginInterface>
     */
    protected function getRestRequestValidatorPlugins(): array
    {
        return [
            new SecurityBlockerCustomerRestRequestValidatorPlugin(),
            new SecurityBlockerAgentRestRequestValidatorPlugin(),
            new AccessTokenRestRequestValidatorPlugin(),
            new AgentAccessTokenRestRequestValidatorPlugin(),
            new SimultaneousAuthenticationRestRequestValidatorPlugin(),
            new CurrencyParameterValidatorPlugin(),
            new PriceModeParameterValidatorPlugin(),
            new ValidateRestRequestAttributesPlugin(),
            new EntityTagRestRequestValidatorPlugin(),
            new CatalogSearchRequestParametersIntegerRestRequestValidatorPlugin(),
            new AccessTokenRestRequestValidatorPlugin(),
            new CartItemProductConfigurationRestRequestValidatorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ControllerAfterActionPluginInterface>
     */
    protected function getControllerAfterActionPlugins(): array
    {
        return [
            new SecurityBlockerCustomerControllerAfterActionPlugin(),
            new SecurityBlockerAgentControllerAfterActionPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ValidateRestRequestPluginInterface>
     */
    protected function getValidateRestRequestPlugins(): array
    {
        return [
            new AnonymousCustomerUniqueIdValidatorPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\FormatResponseHeadersPluginInterface>
     */
    protected function getFormatResponseHeadersPlugins(): array
    {
        return [
            new FormatAuthenticationErrorResponseHeadersPlugin(),
            new EntityTagFormatResponseHeadersPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ControllerBeforeActionPluginInterface>
     */
    protected function getControllerBeforeActionPlugins(): array
    {
        return [
            new SetCustomerBeforeActionPlugin(),
            new SetAnonymousCustomerIdControllerBeforeActionPlugin(),
            new SetPriceModeBeforeActionPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RestUserFinderPluginInterface>
     */
    protected function getRestUserFinderPlugins(): array
    {
        return [
            new RestUserFinderByAccessTokenPlugin(),
            new AgentAccessTokenRestUserFinderPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface>
     */
    protected function getApplicationPlugins(): array
    {
        $plugins = [
            new HttpApplicationPlugin(),
            new SessionApplicationPlugin(),
            new EventDispatcherApplicationPlugin(),
            new GlueApplicationApplicationPlugin(),
            new RouterApplicationPlugin(),
            new StoreApplicationPlugin(),
            new LocaleApplicationPlugin(),
        ];

        if (class_exists(WebProfilerApplicationPlugin::class)) {
            $plugins[] = new WebProfilerApplicationPlugin();
        }

        return $plugins;
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RouterParameterExpanderPluginInterface>
     */
    protected function getRouterParameterExpanderPlugins(): array
    {
        return [
            new AuthorizationRouterParameterExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\GlueApplicationBootstrapPluginInterface>
     */
    protected function getGlueApplicationBootstrapPlugins(): array
    {
        return [
            new BackendApiGlueApplicationBootstrapPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ConventionPluginInterface>
     */
    protected function getConventionPlugins(): array
    {
        return [
            new JsonApiConventionPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ControllerConfigurationCacheCollectorPluginInterface>
     */
    protected function getControllerConfigurationCacheCollectorPlugins(): array
    {
        return [
            new BackendControllerConfigurationCacheCollectorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ApiApplicationEndpointProviderPluginInterface>
     */
    protected function getGlueApplicationRouterProviderPlugins(): array
    {
        return [
            new BackendRouterProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RoutesProviderPluginInterface>
     */
    protected function getRoutesProviderPlugins(): array
    {
        return [
            new BackendCustomRouteRoutesProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\ResourcesProviderPluginInterface>
     */
    protected function getResourcesProviderPlugins(): array
    {
        return [
            new BackendResourcesProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\TableColumnExpanderPluginInterface>
     */
    protected function getTableColumnExpanderPlugins(): array
    {
        return [
            new BackendIsProtectedTableColumnExpanderPlugin(),
        ];
    }
}
