<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Client\ShopConfiguration\Reader;

use Generated\Shared\Transfer\SynchronizationDataTransfer;

class StoreConfigStorageReader
{
    static array $_instanceCache = [];

    public function __construct(
        protected \Spryker\Service\Synchronization\SynchronizationServiceInterface $synchronizationService,
        protected \Spryker\Client\Storage\StorageClientInterface $storageClient,
        protected \Go\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient,
        protected \Spryker\Client\Store\StoreClientInterface $storeClient,
    ) {
    }

    public function getConfig(string $key): string|int|array|bool|null
    {
        $id = $this->tenantBehaviorClient->getCurrentTenantReference();
        if (isset(static::$_instanceCache[$id][$key])) {
            return static::$_instanceCache[$id][$key];
        }

        $storeKey = $this->generateKey($id);
        $storeData = $this->storageClient->get($storeKey);

        if (!$storeData) {
            static::$_instanceCache[$id] = null;

            return null;
        }

        static::$_instanceCache[$id] = $storeData;

        return static::$_instanceCache[$id][$key] ?? null;
    }

    protected function generateKey(string $id): string
    {
        $synchronizationDataTransfer = new SynchronizationDataTransfer();
        $synchronizationDataTransfer->setReference($id)
            ->setStore($this->storeClient->getCurrentStore()->getName())
            ->setTenantReference('');

        return $this->synchronizationService
            ->getStorageKeyBuilder('store_config')
            ->generateKey($synchronizationDataTransfer);
    }
}
