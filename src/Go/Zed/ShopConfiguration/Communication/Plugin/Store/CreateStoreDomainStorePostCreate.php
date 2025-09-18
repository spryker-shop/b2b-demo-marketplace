<?php

namespace Go\Zed\ShopConfiguration\Communication\Plugin\Store;

use Generated\Shared\Transfer\StoreResponseTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomainQuery;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StoreExtension\Dependency\Plugin\StorePostCreatePluginInterface;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Go\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Go\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class CreateStoreDomainStorePostCreate extends AbstractPlugin implements StorePostCreatePluginInterface
{
    /**
     * @inheritDoc
     */
    public function execute(StoreTransfer $storeTransfer): StoreResponseTransfer
    {
        $storeResponseTransfer = (new StoreResponseTransfer())
            ->setStore($storeTransfer)
            ->setIsSuccessful(true);
        if (!$storeTransfer->getTenantReferenceOrFail()) {
            return $storeResponseTransfer;
        }

        $tenantOnboardingFacade = $this->getFactory()->getLocator()->tenantBehavior()->facade();
        $tenantTransfer = $tenantOnboardingFacade
            ->findTenantByIdentifier($storeTransfer->getTenantReference());

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByStoreName($storeTransfer->getNameOrFail())
            ->filterByTenantIdentifier($tenantTransfer->getIdentifier())
            ->findOneOrCreate();

        $storeDomainEntity->setDomainHost(strtolower($storeTransfer->getName() . '-' . $tenantTransfer->getTenantHostOrFail()))
            ->setData([
                'tenant' => $tenantTransfer->getIdentifier(),
                'store' => $storeTransfer->getNameOrFail(),
            ])
            ->save();


        $storeDomainEntities = SpyStoreDomainQuery::create()
            ->filterByTenantIdentifier($tenantTransfer->getIdentifier())
            ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNOTNULL)
            ->find();

        $storeDomainListData = [];
        foreach ($storeDomainEntities as $storeDomainEntity) {
            $storeDomainListData[$storeDomainEntity->getStorename()] = $storeDomainEntity->getDomainHost();
        }

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByTenantIdentifier($tenantTransfer->getIdentifier())
            ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNULL)
            ->findOneOrCreate();
        $storeDomainEntity->setDomainHost($tenantTransfer->getIdentifier());
        $storeDomainEntity->setData($storeDomainListData);
        $storeDomainEntity->save();

        return $storeResponseTransfer;
    }
}
