<?php

namespace Pyz\Zed\ShopConfiguration\Communication\Controller;

use Orm\Zed\TenantOnboarding\Persistence\SpyStoreConfigQuery;
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

        $form = $this->getFactory()
            ->getStoreConfigurationForm($data)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $storeConfigEntity = SpyStoreConfigQuery::create()
                ->filterByTenantIdentifier($tenantId)
                ->filterByStore($storeName)
                ->findOneOrCreate();
            $storeConfigEntity->setData($data);
            $storeConfigEntity->save();

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
