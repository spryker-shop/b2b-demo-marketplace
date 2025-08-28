<?php

namespace Pyz\Zed\SearchElasticsearch\Business\Index;

use Elastica\Client;
use Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifierInterface;
use Spryker\Zed\SearchElasticsearch\Dependency\Facade\SearchElasticsearchToStoreFacadeInterface;
use Spryker\Zed\SearchElasticsearch\SearchElasticsearchConfig;

class Index extends \Spryker\Zed\SearchElasticsearch\Business\Index\Index
{
    protected TenantBehaviorFacadeInterface $tenantBehaviorFacade;

    public function __construct(
        Client $elasticaClient,
        SourceIdentifierInterface $sourceIdentifier,
        SearchElasticsearchConfig $config,
        SearchElasticsearchToStoreFacadeInterface $storeFacade,
        TenantBehaviorFacadeInterface $tenantBehaviorFacade,
    ) {
        parent::__construct($elasticaClient, $sourceIdentifier, $config, $storeFacade);
        $this->tenantBehaviorFacade = $tenantBehaviorFacade;
    }

    /**
     * @param string|null $storeName
     *
     * @return array<string>
     */
    public function getIndexNames(?string $storeName = null): array
    {
        if ($storeName === null) {
            $result = [];
            $originalTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $this->tenantBehaviorFacade->setCurrentTenantId($storeTransfer->getIdTenantOrFail());
                $result = array_merge($result, $this->getAvailableIndexNames($storeTransfer->getName()));
            }
            $this->tenantBehaviorFacade->setCurrentTenantId($originalTenantId);

            return $result;
        }

        return $this->getAvailableIndexNames($storeName);
    }
    /**
     * @param string|null $storeName
     *
     * @return bool
     */
    public function openIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;
            $originalTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $this->tenantBehaviorFacade->setCurrentTenantId($storeTransfer->getIdTenantOrFail());
                $success &= $this->executeOpenIndexes($storeTransfer->getName());
            }
            $this->tenantBehaviorFacade->setCurrentTenantId($originalTenantId);

            return (bool)$success;
        }

        return $this->executeOpenIndexes($storeName);
    }

    /**
     * @param string|null $storeName
     *
     * @return bool
     */
    public function closeIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;
            $originalTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $this->tenantBehaviorFacade->setCurrentTenantId($storeTransfer->getIdTenantOrFail());
                $success &= $this->executeCloseIndexes($storeTransfer->getName());
            }
            $this->tenantBehaviorFacade->setCurrentTenantId($originalTenantId);

            return (bool)$success;
        }

        return $this->executeCloseIndexes($storeName);
    }

    /**
     * @param string|null $storeName
     *
     * @return bool
     */
    public function deleteIndexes(?string $storeName = null): bool
    {
        if (!$storeName) {
            $success = true;
            $originalTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
            foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
                $this->tenantBehaviorFacade->setCurrentTenantId($storeTransfer->getIdTenantOrFail());
                $success &= $this->executeDeleteIndexes($storeTransfer->getName());
            }
            $this->tenantBehaviorFacade->setCurrentTenantId($originalTenantId);

            return (bool)$success;
        }

        return $this->executeDeleteIndexes($storeName);
    }
}
