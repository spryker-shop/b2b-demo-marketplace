<?php

namespace Pyz\Zed\ShopConfiguration\Communication\Controller;

use Orm\Zed\TenantOnboarding\Persistence\SpyStoreConfigQuery;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomainQuery;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class ConfigurationController extends \Spryker\Zed\Kernel\Communication\Controller\AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function indexAction(Request $request): array|\Symfony\Component\HttpFoundation\Response
    {
        $tenantId = $this->getFactory()->getLocator()->tenantBehavior()->facade()->getCurrentTenantId();
        $currentTenant = $tenantId;
        $storeTransfers = $this->getFactory()->getLocator()->store()->facade()->getAllStores();
        if (count($storeTransfers) === 0) {
            $this->addErrorMessage('Create at least one store to use Shop configurator.');
            return $this->redirectResponse('/store-gui/list');
        }

        $storeName = $request->query->get('storeName');
        if (!$storeName) {
            /** @var \Generated\Shared\Transfer\StoreTransfer $storeTransfer */
            $storeTransfer = reset($storeTransfers);
            $storeName = $storeTransfer->getName();
        } else {
            $storeTransfer = $this->getFactory()->getLocator()->store()->facade()->getStoreByName($storeName);
        }
        if (!$tenantId) {
            $tenantId = $storeTransfer->getIdTenantOrFail();
        }

        $storeConfigEntity = SpyStoreConfigQuery::create()
            ->filterByTenantIdentifier($tenantId)
            ->filterByStore($storeName)
            ->findOne();
        $data = [];
        if ($storeConfigEntity) {
            $data = $storeConfigEntity->getData();
        }

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByStoreName($storeName)
            ->filterByTenantIdentifier($tenantId)
            ->findOne();
        if ($storeDomainEntity) {
            $data['shop_domain'] = str_replace('.' . $this->getFactory()->getConfig()->getStoreFrontHost(), '', $storeDomainEntity->getDomainHost());
        }

        $form = $this->getFactory()
            ->getStoreConfigurationForm($data)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['shop_domain'] = $data['shop_domain'] . '.' . $this->getFactory()->getConfig()->getStoreFrontHost();

            $storeConfigEntity = SpyStoreConfigQuery::create()
                ->filterByTenantIdentifier($tenantId)
                ->filterByStore($storeName)
                ->findOneOrCreate();
            $storeConfigEntity->setData($data);
            $storeConfigEntity->save();

            if (isset($data['shop_domain'])) {
                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByStoreName($storeName)
                    ->filterByTenantIdentifier($tenantId)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($data['shop_domain']);
                $storeDomainEntity->setData([
                    'tenant' => $tenantId,
                    'store' => $storeName,
                ]);
                $storeDomainEntity->save();

                $storeDomainEntities = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($tenantId)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNOTNULL)
                    ->find();

                $storeDomainListData = [];
                foreach ($storeDomainEntities as $storeDomainEntity) {
                    $storeDomainListData[$storeDomainEntity->getStorename()] = $storeDomainEntity->getDomainHost();
                }

                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($tenantId)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNULL)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($tenantId);
                $storeDomainEntity->setData($storeDomainListData);
                $storeDomainEntity->save();
            }

            if (!$storeConfigEntity->getIdStoreConfig()) {
                $this->addErrorMessage('Failed to update Store Configuration.');
            } else {
                $this->addSuccessMessage('Store Configuration updated successfully.');
            }
        }

        return $this->viewResponse([
            'form' => $form->createView(),
            'stores' => $storeTransfers,
            'currentStore' => $storeName,
            'currentTenant' => $currentTenant,
        ]);
    }
}
