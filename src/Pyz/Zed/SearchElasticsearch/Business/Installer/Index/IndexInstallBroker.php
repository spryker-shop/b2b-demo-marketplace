<?php

namespace Pyz\Zed\SearchElasticsearch\Business\Installer\Index;

use Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Spryker\Zed\SearchElasticsearch\Business\Definition\Builder\IndexDefinitionBuilderInterface;
use Spryker\Zed\SearchElasticsearch\Dependency\Facade\SearchElasticsearchToStoreFacadeInterface;

class IndexInstallBroker extends \Spryker\Zed\SearchElasticsearch\Business\Installer\Index\IndexInstallBroker
{
    /**
     * @var \Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface
     */
    protected TenantBehaviorFacadeInterface $tenantBehaviorFacade;

    public function __construct(
        IndexDefinitionBuilderInterface $indexDefinitionBuilder,
        array $installer,
        SearchElasticsearchToStoreFacadeInterface $storeFacade,
        TenantBehaviorFacadeInterface $tenantBehaviorFacade,
    ) {
        parent::__construct($indexDefinitionBuilder, $installer, $storeFacade);
        $this->tenantBehaviorFacade = $tenantBehaviorFacade;
    }

    /**
     * @param string|null $storeName
     *
     * @return array<\Generated\Shared\Transfer\IndexDefinitionTransfer>
     */
    protected function getGetIndexDefinitionTransfers(?string $storeName): array
    {
        if ($storeName) {
            return $this->indexDefinitionBuilder->build($storeName);
        }

        /* Required by infrastructure, exists only for BC with DMS OFF mode. */
        if (!$this->storeFacade->isDynamicStoreEnabled()) {
            return $this->indexDefinitionBuilder->build($this->getCurrentStore());
        }

        $indexDefinitionTransfers = [];
        $originalTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
        foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
            $this->tenantBehaviorFacade->setCurrentTenantId($storeTransfer->getIdTenantOrFail());
            $indexDefinitionTransfers = array_merge($indexDefinitionTransfers, $this->indexDefinitionBuilder->build($storeTransfer->getName()));
        }
        $this->tenantBehaviorFacade->setCurrentTenantId($originalTenantId);

        return $indexDefinitionTransfers;
    }
}
