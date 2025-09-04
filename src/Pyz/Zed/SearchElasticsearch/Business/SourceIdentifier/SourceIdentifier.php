<?php

namespace Pyz\Zed\SearchElasticsearch\Business\SourceIdentifier;

class SourceIdentifier extends \Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifier
{
    /**
     * @param string $sourceIdentifier
     * @param string|null $currentStore
     *
     * @return string
     */
    protected function buildIndexName(string $sourceIdentifier, ?string $currentStore = null): string
    {
        if ($currentStore !== null) {
            /** @var \Spryker\Zed\Store\Business\StoreFacadeInterface $storeFacade */
            $storeFacade = \Spryker\Zed\Kernel\Locator::getInstance()->store()->facade();
            $storeTransfer = $storeFacade->getStoreByName($currentStore);

            $indexParameters = [
                $this->searchElasticsearchConfig->getIndexPrefix(),
                $storeTransfer->getIdTenantOrFail(),
                $currentStore,
                $sourceIdentifier,
            ];
        } else {
            $indexParameters = [
                $this->searchElasticsearchConfig->getIndexPrefix(),
                $currentStore,
                $sourceIdentifier,
            ];
        }


        return mb_strtolower(implode('_', array_filter($indexParameters)));
    }
}
