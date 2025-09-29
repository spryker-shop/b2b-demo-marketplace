<?php

namespace Pyz\Client\Storage;

use Spryker\Client\Storage\StorageClient as SprykerStorageClient;
use Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface;

/**
 * @method \Pyz\Client\Storage\StorageFactory getFactory()
 */
class StorageClient extends SprykerStorageClient implements StorageClientInterface
{
    public static StoragePluginInterface|null $cacheService = null;

    public function getMulti(array $keys, bool $decode = false): array
    {
        $entries = parent::getMulti($keys);

        if (!$decode) {
            return $entries;
        }

        $result = [];
        foreach ($entries as $k => $v) {
            $result[$k] = $this->jsonDecode($v);
        }

        return $result;
    }

    public function getCacheService(): StoragePluginInterface
    {
        if (static::$cacheService === null) {
            static::$cacheService = $this->getFactory()->createCacheService();
        }

        return static::$cacheService;
    }

    protected function loadKeysFromCache(): void
    {
        static::$cachedKeys = [];
        $cacheKey = $this->buildCacheKey();

        if (!$cacheKey) {
            return;
        }

        $cachedKeys = $this->getCacheService()->get($cacheKey);

        if ($cachedKeys && is_array($cachedKeys)) {
            foreach ($cachedKeys as $key) {
                static::$cachedKeys[$key] = static::KEY_INIT;
            }
        }
    }
}
