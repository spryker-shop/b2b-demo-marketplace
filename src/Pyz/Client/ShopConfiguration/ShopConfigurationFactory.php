<?php

declare(strict_types=1);

namespace Pyz\Client\ShopConfiguration;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;

class ShopConfigurationFactory extends AbstractFactory
{
    /**
     * @return \Spryker\Client\Storage\StorageClientInterface
     */
    public function getStorageClient(): StorageClientInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::CLIENT_STORAGE);
    }

    /**
     * @return \Spryker\Client\Store\StoreClientInterface
     */
    public function getStoreClient(): StoreClientInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::CLIENT_STORE);
    }
}
