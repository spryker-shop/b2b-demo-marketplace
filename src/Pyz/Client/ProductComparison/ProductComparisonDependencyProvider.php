<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison;

use Pyz\Client\ProductComparison\Plugin\SessionStorageStrategyPlugin;
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Client\Storage\StorageClientInterface;

class ProductComparisonDependencyProvider extends AbstractDependencyProvider
{
    public const CLIENT_STORAGE = 'STORAGE_CLIENT';

    public const CLIENT_SESSION = 'SESSION_CLIENT';

    public const PLUGINS_STORAGE_STRATEGY = 'STORAGE_STRATEGY_PLUGINS';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $this->addStorageClient($container);
        $this->addSessionClient($container);
        $this->addStorageStrategyPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return void
     */
    private function addStorageClient(Container $container): void
    {
        $container->set(static::CLIENT_STORAGE, static function (Container $container): StorageClientInterface {
            return $container->getLocator()->storage()->client();
        });
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return void
     */
    private function addSessionClient(Container $container): void
    {
        $container->set(static::CLIENT_SESSION, static function (Container $container): SessionClientInterface {
            return $container->getLocator()->session()->client();
        });
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return void
     */
    private function addStorageStrategyPlugins(Container $container): void
    {
        $container->set(static::PLUGINS_STORAGE_STRATEGY, function (): array {
            return $this->getStorageStrategyPlugins();
        });
    }

    /**
     * @return array<\Pyz\Client\ProductComparison\Dependency\StorageStrategyPluginInterface>
     */
    private function getStorageStrategyPlugins(): array
    {
        return [
            new SessionStorageStrategyPlugin(),
        ];
    }
}
