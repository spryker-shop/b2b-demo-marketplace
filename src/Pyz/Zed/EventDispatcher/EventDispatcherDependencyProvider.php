<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\EventDispatcher;

use Spryker\Shared\Http\Plugin\EventDispatcher\ResponseListenerEventDispatcherPlugin;
use Spryker\Zed\Acl\Communication\Plugin\EventDispatcher\AccessControlEventDispatcherPlugin;
use Spryker\Zed\AgentSecurityBlockerMerchantPortalGui\Communication\Plugin\EventDispatcher\SecurityBlockerAgentMerchantPortalEventDispatcherPlugin;
use Spryker\Zed\Application\Communication\Plugin\EventDispatcher\HeadersSecurityEventDispatcherPlugin;
use Spryker\Zed\ErrorHandler\Communication\Plugin\EventDispatcher\ErrorPageEventDispatcherPlugin;
use Spryker\Zed\EventBehavior\Communication\Plugin\EventDispatcher\EventBehaviorEventDispatcherPlugin;
use Spryker\Zed\EventDispatcher\EventDispatcherDependencyProvider as SprykerEventDispatcherDependencyProvider;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\CacheControlHeaderEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\CookieEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\EnvironmentInfoHeaderEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\FragmentEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\HstsHeaderEventDispatcher;
use Spryker\Zed\Kernel\Communication\Plugin\AutoloaderCacheEventDispatcherPlugin;
use Spryker\Zed\Kernel\Communication\Plugin\EventDispatcher\RedirectUrlValidationEventDispatcherPlugin;
use Spryker\Zed\Locale\Communication\Plugin\EventDispatcher\LocaleEventDispatcherPlugin;
use Spryker\Zed\MerchantPortalApplication\Communication\Plugin\EventDispatcher\HeadersSecurityEventDispatcherPlugin as MerchantPortalHeadersSecurityEventDispatcherPlugin;
use Spryker\Zed\Monitoring\Communication\Plugin\EventDispatcher\GatewayMonitoringRequestTransactionEventDispatcherPlugin;
use Spryker\Zed\Monitoring\Communication\Plugin\EventDispatcher\MonitoringRequestTransactionEventDispatcherPlugin;
use Spryker\Zed\Profiler\Communication\Plugin\EventDispatcher\ProfilerRequestEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RequestAttributesEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RouterListenerEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RouterLocaleEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RouterSslRedirectEventDispatcherPlugin;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Plugin\EventDispatcher\SecurityBlockerBackofficeUserEventDispatcherPlugin;
use Spryker\Zed\SecurityBlockerMerchantPortalGui\Communication\Plugin\EventDispatcher\SecurityBlockerMerchantPortalUserEventDispatcherPlugin;
use Spryker\Zed\Session\Communication\Plugin\EventDispatcher\SaveSessionEventDispatcherPlugin;
use Spryker\Zed\Session\Communication\Plugin\EventDispatcher\SessionEventDispatcherPlugin;
use Spryker\Zed\Twig\Communication\Plugin\EventDispatcher\TwigEventDispatcherPlugin;
use Spryker\Zed\ZedRequest\Communication\Plugin\EventDispatcher\GatewayControllerEventDispatcherPlugin;

class EventDispatcherDependencyProvider extends SprykerEventDispatcherDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface>
     */
    protected function getEventDispatcherPlugins(): array
    {
        $plugins = [
            new AccessControlEventDispatcherPlugin(),
            new EventBehaviorEventDispatcherPlugin(),
            new HeadersSecurityEventDispatcherPlugin(),
            new LocaleEventDispatcherPlugin(),
            new MonitoringRequestTransactionEventDispatcherPlugin(),
            new RouterLocaleEventDispatcherPlugin(),
            new RouterListenerEventDispatcherPlugin(),
            new RouterSslRedirectEventDispatcherPlugin(),
            new CookieEventDispatcherPlugin(),
            new FragmentEventDispatcherPlugin(),
            new HstsHeaderEventDispatcher(),
            new CacheControlHeaderEventDispatcherPlugin(),
            new TwigEventDispatcherPlugin(),
            new SessionEventDispatcherPlugin(),
            new SaveSessionEventDispatcherPlugin(),
            new AutoloaderCacheEventDispatcherPlugin(),
            new RequestAttributesEventDispatcherPlugin(),
            new ResponseListenerEventDispatcherPlugin(),
            new ErrorPageEventDispatcherPlugin(),
            new RedirectUrlValidationEventDispatcherPlugin(),
            new EnvironmentInfoHeaderEventDispatcherPlugin(),
            new SecurityBlockerBackofficeUserEventDispatcherPlugin(),
            new SecurityBlockerMerchantPortalUserEventDispatcherPlugin(),
            new SecurityBlockerAgentMerchantPortalEventDispatcherPlugin(),
            new GatewayControllerEventDispatcherPlugin(),
        ];

        if (class_exists(ProfilerRequestEventDispatcherPlugin::class)) {
            $plugins[] = new ProfilerRequestEventDispatcherPlugin();
        }

        return $plugins;
    }

    /**
     * @return array<\Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface>
     */
    protected function getBackofficeEventDispatcherPlugins(): array
    {
        $plugins = [
            new AccessControlEventDispatcherPlugin(),
            new EventBehaviorEventDispatcherPlugin(),
            new HeadersSecurityEventDispatcherPlugin(),
            new LocaleEventDispatcherPlugin(),
            new MonitoringRequestTransactionEventDispatcherPlugin(),
            new RouterLocaleEventDispatcherPlugin(),
            new RouterListenerEventDispatcherPlugin(),
            new RouterSslRedirectEventDispatcherPlugin(),
            new CookieEventDispatcherPlugin(),
            new FragmentEventDispatcherPlugin(),
            new HstsHeaderEventDispatcher(),
            new CacheControlHeaderEventDispatcherPlugin(),
            new TwigEventDispatcherPlugin(),
            new SessionEventDispatcherPlugin(),
            new SaveSessionEventDispatcherPlugin(),
            new AutoloaderCacheEventDispatcherPlugin(),
            new RequestAttributesEventDispatcherPlugin(),
            new ResponseListenerEventDispatcherPlugin(),
            new ErrorPageEventDispatcherPlugin(),
            new RedirectUrlValidationEventDispatcherPlugin(),
            new SecurityBlockerBackofficeUserEventDispatcherPlugin(),
            new SecurityBlockerMerchantPortalUserEventDispatcherPlugin(),
            new EnvironmentInfoHeaderEventDispatcherPlugin(),
        ];

        if (class_exists(ProfilerRequestEventDispatcherPlugin::class)) {
            $plugins[] = new ProfilerRequestEventDispatcherPlugin();
        }

        return $plugins;
    }

    /**
     * @return array<\Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface>
     */
    protected function getMerchantPortalEventDispatcherPlugins(): array
    {
        return [
            new MerchantPortalHeadersSecurityEventDispatcherPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface>
     */
    protected function getBackendGatewayEventDispatcherPlugins(): array
    {
        return [
            new GatewayMonitoringRequestTransactionEventDispatcherPlugin(),
            new GatewayControllerEventDispatcherPlugin(),
            new RouterListenerEventDispatcherPlugin(),
            new ResponseListenerEventDispatcherPlugin(),
            new AutoloaderCacheEventDispatcherPlugin(),
            new EventBehaviorEventDispatcherPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface>
     */
    protected function getBackendApiEventDispatcherPlugins(): array
    {
        return [
            new MonitoringRequestTransactionEventDispatcherPlugin(),
            new RouterListenerEventDispatcherPlugin(),
            new ResponseListenerEventDispatcherPlugin(),
            new AutoloaderCacheEventDispatcherPlugin(),
        ];
    }
}
