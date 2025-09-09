<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\Storage;

use Spryker\Client\Kernel\Container;
use Spryker\Client\Storage\StorageDependencyProvider as SprykerStorageDependencyProvider;
use Spryker\Client\StorageDatabase\Plugin\StorageDatabasePlugin;
use Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface;
use Spryker\Client\StorageRedis\Plugin\StorageRedisPlugin;

class StorageDependencyProvider extends SprykerStorageDependencyProvider
{
    /**
     * @var string
     */
    public const PLUGIN_CACHE_STORAGE = 'PLUGIN_CACHE_STORAGE';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addCacheStoragePlugin($container);

        return $container;
    }

    protected function getStoragePlugin(): ?StoragePluginInterface
    {
        return new StorageDatabasePlugin();
    }

    protected function getCacheStoragePlugin(): ?StoragePluginInterface
    {
        return new StorageRedisPlugin();
    }

    protected function addCacheStoragePlugin(Container $container): Container
    {
        $container->set(static::PLUGIN_CACHE_STORAGE, function () {
            return $this->getCacheStoragePlugin();
        });

        return $container;
    }
}
