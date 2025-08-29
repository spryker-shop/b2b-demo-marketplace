<?php

namespace Pyz\Client\Storage\Cache;

use Pyz\Client\Storage\Cache\StorageCacheStrategyHelper as PyzStorageCacheStrategyHelper;
use Spryker\Client\Storage\Cache\StorageCacheStrategyFactory as SprykerStorageCacheStrategyFactory;

class StorageCacheStrategyFactory extends SprykerStorageCacheStrategyFactory
{
    /**
     * @return \Spryker\Client\Storage\Cache\StorageCacheStrategyHelper
     */
    protected function createStorageCacheStrategyHelper(): PyzStorageCacheStrategyHelper
    {
        return new PyzStorageCacheStrategyHelper(
            $this->storageClient,
            $this->storageClientConfig,
        );
    }
}
