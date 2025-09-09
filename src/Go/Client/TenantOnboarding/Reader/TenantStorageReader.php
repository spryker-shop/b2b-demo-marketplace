<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Client\TenantOnboarding\Reader;

use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Generated\Shared\Transfer\TenantStorageTransfer;

class TenantStorageReader
{
    static array $_instanceCache = [];

    public function __construct(
        protected \Spryker\Service\Synchronization\SynchronizationServiceInterface $synchronizationService,
        protected \Spryker\Client\Storage\StorageClientInterface $storageClient
    ) {
    }

    public function findTenantByID(string $id): ?TenantStorageTransfer
    {
        if (isset(static::$_instanceCache[$id])) {
            return static::$_instanceCache[$id];
        }

        $storeKey = $this->generateKey($id);
        $storeData = $this->storageClient->getService()->get($storeKey);

        if (!$storeData) {
            static::$_instanceCache[$id] = null;

            return null;
        }

        static::$_instanceCache[$id] = (new TenantStorageTransfer())
            ->fromArray($storeData, true)
            ->setIdTenant($id);

        return static::$_instanceCache[$id];
    }

    protected function generateKey(string $id): string
    {
        $synchronizationDataTransfer = new SynchronizationDataTransfer();
        $synchronizationDataTransfer->setReference($id)
            ->setTenantReference('');

        return $this->synchronizationService
            ->getStorageKeyBuilder('tenant')
            ->generateKey($synchronizationDataTransfer);
    }
}
