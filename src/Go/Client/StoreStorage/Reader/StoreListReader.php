<?php

namespace Go\Client\StoreStorage\Reader;

class StoreListReader extends \Spryker\Client\StoreStorage\Reader\StoreListReader
{
    /**
     * @var array<string>|null
     */
    static protected $_storeNames = null;

    /**
     * @return array<string>
     */
    public function getStoresNames(): array
    {
        if (static::$_storeNames === null) {
            $key = $this->generateKey();
            $storeData = (new \Pyz\Client\Storage\StorageClient())->getService()->get($key);
            static::$_storeNames = $storeData['stores'] ?? [];
        }

        return static::$_storeNames;
    }
}
