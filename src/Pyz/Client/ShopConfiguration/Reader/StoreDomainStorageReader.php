<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Pyz\Client\ShopConfiguration\Reader;

use Generated\Shared\Transfer\SynchronizationDataTransfer;

class StoreDomainStorageReader
{
    static array $_instanceCache = [];

    public function __construct(
        protected \Spryker\Service\Synchronization\SynchronizationServiceInterface $synchronizationService,
        protected \Spryker\Client\Storage\StorageClientInterface $storageClient,
        protected \Pyz\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient,
        protected \Spryker\Client\Store\StoreClientInterface $storeClient,
    ) {
    }

    public function getConfig(string $key): ?array
    {
        if (isset(static::$_instanceCache[$key])) {
            return static::$_instanceCache[$key];
        }

        $storeKey = $this->generateKey($key);
        $storeData = $this->storageClient->getService()->get($storeKey);

        if (!$storeData) {
            static::$_instanceCache[$key] = null;

            return null;
        }

        static::$_instanceCache[$key] = $storeData;

        return static::$_instanceCache[$key] ?? null;
    }

    protected function generateKey(string $id): string
    {
        $synchronizationDataTransfer = new SynchronizationDataTransfer();
        $synchronizationDataTransfer->setReference($id)
            ->setIdTenant('')
            ->setStore('')
            ->setLocale('');

        return $this->synchronizationService
            ->getStorageKeyBuilder('store_domain')
            ->generateKey($synchronizationDataTransfer);
    }
}
