<?php

namespace Pyz\Client\MerchantStorage;

use Pyz\Client\MerchantStorage\Storage\MerchantStorageReader;
use Spryker\Client\MerchantStorage\Storage\MerchantStorageReaderInterface;

class MerchantStorageFactory extends \Spryker\Client\MerchantStorage\MerchantStorageFactory
{
    /**
     * @return \Spryker\Client\MerchantStorage\Storage\MerchantStorageReaderInterface
     */
    public function createMerchantStorageReader(): MerchantStorageReaderInterface
    {
        return new MerchantStorageReader(
            $this->createMerchantStorageMapper(),
            $this->getSynchronizationService(),
            $this->getStorageClient(),
            $this->getUtilEncodingService(),
            $this->getStoreClient(),
        );
    }
}
