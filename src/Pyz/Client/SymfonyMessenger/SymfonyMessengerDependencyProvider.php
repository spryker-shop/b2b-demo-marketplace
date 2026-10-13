<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\SymfonyMessenger;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerDependencyProvider as SprykerSymfonyMessengerDependencyProvider;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\AsyncSchedulerJobMessageMappingProviderPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\AsyncSchedulerJobTransportConfigProviderPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\AsyncSchedulerJobTransportFactoryProviderPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\CompiledCronTransportGroupAwarePlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\DisabledSchedulerJobTransportGuardPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\SchedulerAvailableTransportConfigProviderPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\SchedulerMessageMappingProviderPlugin;
use Spryker\Client\SymfonyScheduler\Plugin\SymfonyMessenger\SchedulerTransportFactoryProviderPlugin;

class SymfonyMessengerDependencyProvider extends SprykerSymfonyMessengerDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportFactoryProviderPluginInterface>
     */
    protected function getTransportFactoryProviderPlugins(): array
    {
        return [
            new SchedulerTransportFactoryProviderPlugin(),
            new AsyncSchedulerJobTransportFactoryProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportConfigProviderPluginInterface>
     */
    protected function getAvailableTransportConfigProviderPlugins(): array
    {
        return [
            new SchedulerAvailableTransportConfigProviderPlugin(),
            new AsyncSchedulerJobTransportConfigProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface>
     */
    protected function getMessageMappingProviderPlugins(): array
    {
        return [
            new SchedulerMessageMappingProviderPlugin(),
            new AsyncSchedulerJobMessageMappingProviderPlugin(),
        ];
    }

    protected function getGroupAwareTransportsPlugins(): array
    {
        return [
            new CompiledCronTransportGroupAwarePlugin(),
        ];
    }
}
