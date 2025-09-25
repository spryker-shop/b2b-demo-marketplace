<?php

namespace Go\Client\StoreStorage\Reader;

use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Spryker\Client\StoreStorage\Dependency\Client\StoreStorageToStorageClientInterface;
use Spryker\Client\StoreStorage\Dependency\Service\StoreStorageToSynchronizationServiceInterface;
use Spryker\Shared\StoreStorage\StoreStorageConfig;

class StoreStorageReader extends \Spryker\Client\StoreStorage\Reader\StoreStorageReader
{
    public function __construct(
        StoreStorageToSynchronizationServiceInterface $synchronizationService,
        StoreStorageToStorageClientInterface $storageClient,
        protected \Go\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient
    ) {
        parent::__construct($synchronizationService, $storageClient);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function generateKey(string $name): string
    {
        $synchronizationDataTransfer = new SynchronizationDataTransfer();
        $synchronizationDataTransfer->setReference($name)
            ->setTenantReference($this->tenantBehaviorClient->getCurrentTenantReference());

        return $this->synchronizationService
            ->getStorageKeyBuilder(StoreStorageConfig::STORE_RESOURCE_NAME)
            ->generateKey($synchronizationDataTransfer);
    }
}
