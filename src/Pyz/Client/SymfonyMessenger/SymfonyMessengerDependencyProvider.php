<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\SymfonyMessenger;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerDependencyProvider as SprykerSymfonyMessengerDependencyProvider;
use Spryker\Zed\SymfonyScheduler\Communication\Plugin\SymfonyMessenger\CompiledCronTransportGroupAwarePlugin;
use Spryker\Zed\SymfonyScheduler\Communication\Plugin\SymfonyMessenger\SchedulerAvailableTransportProviderPlugin;
use Spryker\Zed\SymfonyScheduler\Communication\Plugin\SymfonyMessenger\SchedulerMessageMappingProviderPlugin;
use Spryker\Zed\SymfonyScheduler\Communication\Plugin\SymfonyMessenger\SchedulerTransportFactoryProviderPlugin;

class SymfonyMessengerDependencyProvider extends SprykerSymfonyMessengerDependencyProvider
{
    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: transport plugin stack registers Zed scheduler plugins by design. Do not suppress for new code.
     *
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportFactoryProviderPluginInterface>
     */
    protected function getTransportFactoryProviderPlugins(): array
    {
        return [
            new SchedulerTransportFactoryProviderPlugin(),
        ];
    }

    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: transport plugin stack registers Zed scheduler plugins by design. Do not suppress for new code.
     *
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportProviderPluginInterface>
     */
    protected function getAvailableTransportProviderPlugins(): array
    {
        return [
            new SchedulerAvailableTransportProviderPlugin(),
        ];
    }

    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: transport plugin stack registers Zed scheduler plugins by design. Do not suppress for new code.
     *
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface>
     */
    protected function getMessageMappingProviderPlugins(): array
    {
        return [
            new SchedulerMessageMappingProviderPlugin(),
        ];
    }

    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: transport plugin stack registers Zed scheduler plugins by design. Do not suppress for new code.
     *
     * @return array<\Spryker\Zed\SymfonyScheduler\Communication\Plugin\SymfonyMessenger\CompiledCronTransportGroupAwarePlugin>
     */
    protected function getGroupAwareTransportsPlugins(): array
    {
        return [
            new CompiledCronTransportGroupAwarePlugin(),
        ];
    }
}
