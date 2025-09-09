<?php

namespace Pyz\Client\Storage;

use Spryker\Client\Storage\StorageConfig as SprykerStorageConfig;

class StorageConfig extends SprykerStorageConfig
{
    /**
     * @api
     *
     * @return bool
     */
    public function isStorageCachingEnabled(): bool
    {
        return true;
    }
}
