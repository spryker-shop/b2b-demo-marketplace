<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\SymfonyMessenger;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerDependencyProvider as SprykerSymfonyMessengerDependencyProvider;
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
        ];
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportConfigProviderPluginInterface>
     */
    protected function getAvailableTransportConfigProviderPlugins(): array
    {
        return [
            new SchedulerAvailableTransportConfigProviderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface>
     */
    protected function getMessageMappingProviderPlugins(): array
    {
        return [
            new SchedulerMessageMappingProviderPlugin(),
        ];
    }

    protected function getGroupAwareTransportsPlugins(): array
    {
        return [
            new CompiledCronTransportGroupAwarePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportConsumeGuardPluginInterface>
     */
    protected function getTransportConsumeGuardPlugins(): array
    {
        return [
            new DisabledSchedulerJobTransportGuardPlugin(),
        ];
    }
}
