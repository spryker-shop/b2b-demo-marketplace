<?php

namespace Pyz\Zed\Store\Business\Cache;

use Generated\Shared\Transfer\StoreTransfer;
use Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Spryker\Zed\Store\Business\Exception\StoreCacheNotFoundException;

class StoreCache extends \Spryker\Zed\Store\Business\Cache\StoreCache
{

    /**
     * @var array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected static $storeTransfersCacheByStoreId = [];

    /**
     * @var array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected static $storeTransferCacheByStoreName = [];

    /**
     * @var array<string, array<string, \Generated\Shared\Transfer\StoreTransfer>> $storeTransferCacheByStoreNameTenantId
     */
    protected static $storeTransferCacheByStoreNameTenantId = [];

    public function __construct(
        protected TenantBehaviorFacadeInterface $tenantBehaviorFacade,
    ) {
    }

    /**
     * @param int $idStore
     *
     * @return bool
     */
    public function hasStoreByStoreId(int $idStore): bool
    {
        return isset(static::$storeTransfersCacheByStoreId[$idStore]);
    }

    /**
     * @param string $storeName
     *
     * @return bool
     */
    public function hasStoreByStoreName(string $storeName): bool
    {
        $currentTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
        if ($currentTenantId) {
            return isset(static::$storeTransferCacheByStoreNameTenantId[$storeName][$currentTenantId]);
        }
        return isset(static::$storeTransferCacheByStoreName[$storeName]);
    }

    /**
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     *
     * @return void
     */
    public function cacheStore(StoreTransfer $storeTransfer): void
    {
        static::$storeTransferCacheByStoreName[$storeTransfer->getName()] = $storeTransfer;
        static::$storeTransfersCacheByStoreId[$storeTransfer->getIdStore()] = $storeTransfer;
        $currentTenantId = $storeTransfer->getIdTenant();
        if ($currentTenantId) {
            static::$storeTransferCacheByStoreNameTenantId[$storeTransfer->getName()][$currentTenantId] = $storeTransfer;
        }
    }

    /**
     * @param int $idStore
     *
     * @throws \Spryker\Zed\Store\Business\Exception\StoreCacheNotFoundException
     *
     * @return \Generated\Shared\Transfer\StoreTransfer
     */
    public function getStoreByStoreId(int $idStore): StoreTransfer
    {
        if (!$this->hasStoreByStoreId($idStore)) {
            throw new StoreCacheNotFoundException();
        }

        return static::$storeTransfersCacheByStoreId[$idStore];
    }

    /**
     * @param string $storeName
     *
     * @throws \Spryker\Zed\Store\Business\Exception\StoreCacheNotFoundException
     *
     * @return \Generated\Shared\Transfer\StoreTransfer
     */
    public function getStoreByStoreName(string $storeName): StoreTransfer
    {
        if (!$this->hasStoreByStoreName($storeName)) {
            throw new StoreCacheNotFoundException();
        }
        $currentTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
        if ($currentTenantId) {
            return static::$storeTransferCacheByStoreNameTenantId[$storeName][$currentTenantId];
        }

        return static::$storeTransferCacheByStoreName[$storeName];
    }
}
