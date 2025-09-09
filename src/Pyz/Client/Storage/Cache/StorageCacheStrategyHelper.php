<?php

namespace Pyz\Client\Storage\Cache;

use Spryker\Client\Storage\Cache\StorageCacheStrategyHelper as SprykerStorageCacheStrategyHelper;

/**
 * @property \Pyz\Client\Storage\StorageClientInterface $storageClient
 */
class StorageCacheStrategyHelper extends SprykerStorageCacheStrategyHelper
{
    public function setCache($cacheKey): void
    {
        $ttl = $this->storageConfig->getStorageCacheTtl();
        $this->storageClient->getCacheService()->set($cacheKey, json_encode(array_keys($this->getCachedKeys())), $ttl);
    }
}
