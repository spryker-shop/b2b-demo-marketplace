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

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->loadCacheKeysAndValues();

        if (!array_key_exists($key, static::$bufferedValues)) {
            static::$cachedKeys[$key] = static::KEY_NEW;

            $value = $this->getService()->get($key);

            static::$bufferedValues[$key] = $value;

            return $value;
        }

        static::$cachedKeys[$key] = static::KEY_USED;

        if (!array_key_exists($key, static::$bufferedDecodedValues)) {
            if (is_string(static::$bufferedValues[$key])) {
                static::$bufferedDecodedValues[$key] = $this->jsonDecode(static::$bufferedValues[$key]);
            } else {
                static::$bufferedDecodedValues[$key] = static::$bufferedValues[$key];
            }
        }

        return static::$bufferedDecodedValues[$key];
    }
}
