<?php

namespace Pyz\Client\SearchElasticsearch\Index\IndexNameResolver;

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

        /** @var \Spryker\Client\Store\StoreClientInterface $storeClient */
        $storeClient = \Spryker\Client\Kernel\Locator::getInstance()->store()->client();
        $storeTransfer = $storeClient->getStoreByName($storeName);

        $indexParameters = [
            $this->searchElasticsearchConfig->getIndexPrefix(),
            $storeTransfer->getIdTenantOrFail(),
            $storeName,
            $sourceIdentifier,
        ];

        return mb_strtolower(implode('_', array_filter($indexParameters)));
    }
}
