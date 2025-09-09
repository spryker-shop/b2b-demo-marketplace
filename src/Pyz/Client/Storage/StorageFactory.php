<?php

namespace Pyz\Client\Storage;

use Pyz\Client\Storage\Cache\StorageCacheStrategyFactory;
use Spryker\Client\Storage\Cache\StorageCacheStrategyFactory as SprykerStorageCacheStrategyFactory;
use Spryker\Client\Storage\StorageFactory as SprykerStorageFactory;
use Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface;

class StorageFactory extends SprykerStorageFactory
{
    public function createCacheService(): StoragePluginInterface
    {
        static $storageCacheService;

        if (!$storageCacheService) {
            $storageCacheService = $this->getCacheStoragePlugin();
        }

        return $storageCacheService;
    }

    protected function getCacheStoragePlugin(): StoragePluginInterface
    {
        return $this->getProvidedDependency(StorageDependencyProvider::PLUGIN_CACHE_STORAGE);
    }

    /**
     * @return \Spryker\Client\Storage\Cache\StorageCacheStrategyFactory
     */
    protected function createStorageClientStrategyFactory(): SprykerStorageCacheStrategyFactory
    {
        return new StorageCacheStrategyFactory(
            $this->getStorageClient(),
            $this->getStorageClientConfig(),
        );
    }
}
