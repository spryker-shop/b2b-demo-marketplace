<?php

namespace Pyz\Client\Storage;

use Spryker\Client\Storage\StorageClientInterface as SprykerStorageClientInterface;
use Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface;

interface StorageClientInterface extends SprykerStorageClientInterface
{
    /**
     * Specification:
     * - Retrieves multiple keys from storage.
     *
     * @api
     *
     * @param array $keys
     * @param bool $decode
     *
     * @return array
     */
    public function getMulti(array $keys, bool $decode = false): array;

    /**
     * Specification:
     * - Returns the cache service.
     *
     * @api
     *
     * @return \Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface
     */
    public function getCacheService(): StoragePluginInterface;
}
