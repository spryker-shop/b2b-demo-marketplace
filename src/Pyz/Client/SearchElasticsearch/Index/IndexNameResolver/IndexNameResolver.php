<?php

namespace Pyz\Client\SearchElasticsearch\Index\IndexNameResolver;

use Generated\Shared\Transfer\SynchronizationDataTransfer;

class IndexNameResolver extends \Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolver
{
    /**
     * @param string $sourceIdentifier
     * @param string|null $storeName
     *
     * @return string
     */
    public function resolve(string $sourceIdentifier, ?string $storeName = null): string
    {
        $storeName = $storeName ?? $this->getStoreName();
        $tenantId = $this->getIdTenant($storeName);

        $indexParameters = [
            $this->searchElasticsearchConfig->getIndexPrefix(),
            $tenantId,
            $storeName,
            $sourceIdentifier,
        ];

        return mb_strtolower(implode('_', array_filter($indexParameters)));
    }

    protected function getIdTenant(string $storeName): string
    {
        if (APPLICATION === 'YVES' || APPLICATION === 'GLUE') {
            /** @var \Spryker\Client\Store\StoreClientInterface $storeFacade */
            $storeFacade = \Spryker\Client\Kernel\Locator::getInstance()->store()->client();
            $storeTransfer = $storeFacade->getStoreByName($storeName);

            return $storeTransfer->getIdTenantOrFail();
        }

        /** @var \Spryker\Zed\Store\Business\StoreFacadeInterface $storeFacade */
        $storeFacade = \Spryker\Zed\Kernel\Locator::getInstance()->store()->facade();
        $storeTransfer = $storeFacade->getStoreByName($storeName);

        return $storeTransfer->getIdTenantOrFail();
    }
}
