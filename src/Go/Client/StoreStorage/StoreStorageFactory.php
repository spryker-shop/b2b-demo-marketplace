<?php

namespace Go\Client\StoreStorage;

use Go\Client\StoreStorage\Reader\StoreListReader;
use Go\Client\StoreStorage\Reader\StoreStorageReader;
use Spryker\Client\StoreStorage\Reader\StoreStorageReaderInterface;

class StoreStorageFactory extends \Spryker\Client\StoreStorage\StoreStorageFactory
{
    /**
     * @return \Spryker\Client\StoreStorage\Reader\StoreListReader
     */
    public function createStoreListReader(): StoreListReader
    {
        return new StoreListReader(
            $this->getSynchronizationService(),
            $this->getStorageClient(),
        );
    }

    /**
     * @return \Spryker\Client\StoreStorage\Reader\StoreStorageReaderInterface
     */
    public function createStoreStorageReader(): StoreStorageReaderInterface
    {
        return new StoreStorageReader(
            $this->getSynchronizationService(),
            $this->getStorageClient(),
            $this->getContainer()->getLocator()->tenantBehavior()->client(),
        );
    }
}
