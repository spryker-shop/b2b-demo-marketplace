<?php

namespace Go\Zed\Store\Business;

use Go\Zed\Store\Business\Cache\StoreCache;
use Spryker\Zed\Store\Business\Cache\StoreCacheInterface;

class StoreBusinessFactory extends \Spryker\Zed\Store\Business\StoreBusinessFactory
{
    public function createStoreCache(): StoreCacheInterface
    {
        return new StoreCache(
            $this->getContainer()->getLocator()->tenantBehavior()->facade(),
        );
    }
}
