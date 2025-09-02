<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\ShopConfiguration\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomain;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomainQuery;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 * @method \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 */
class CreateStoreDomainsOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Create Store Domains';
    }

    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $registrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantOnboardingStepResultTransfer
     */
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true)
            ->setTenantRegistration($tenantRegistrationTransfer);

        $storeTransfers = $this->getFactory()->getStoreFacade()->getAllStores();
        if (count($storeTransfers) === 0) {
            $result->setIsSuccessful(true);

            return $result;
        }

        $storeNames = [];
        foreach ($storeTransfers as $storeTransfer) {
            $storeNames[] = $storeTransfer->getNameOrFail();
        }

        $storeDomainEntities = SpyStoreDomainQuery::create()
            ->filterByStore_In($storeNames)
            ->filterByTenantIdentifier($tenantRegistrationTransfer->getTenantNameOrFail())
            ->find();

        $processedStores = [];
        foreach ($storeDomainEntities as $storeDomainEntity) {
            $processedStores[] = $storeDomainEntity->getStore();
        }

        $createdHosts = [];
        foreach ($storeTransfers as $storeTransfer) {
            if (in_array($storeTransfer->getNameOrFail(), $processedStores, true)) {
                continue;
            }
            $generateTenantHost = $this->generateTenantHost($tenantRegistrationTransfer->getTenant(), $storeTransfer->getNameOrFail());
            (new SpyStoreDomain())
                ->setStorename($storeTransfer->getNameOrFail())
                ->setTenantIdentifier($tenantRegistrationTransfer->getTenantNameOrFail())
                ->setDomainHost($generateTenantHost)
                ->setData([
                    'tenant' => $tenantRegistrationTransfer->getTenantNameOrFail(),
                    'store' => $storeTransfer->getNameOrFail(),
                ])
                ->save();
            $createdHosts[] = $generateTenantHost;
        }


        $storeDomainEntities = SpyStoreDomainQuery::create()
            ->filterByTenantIdentifier($tenantRegistrationTransfer->getTenantNameOrFail())
            ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNOTNULL)
            ->find();

        $storeDomainListData = [];
        foreach ($storeDomainEntities as $storeDomainEntity) {
            $storeDomainListData[$storeDomainEntity->getStorename()] = $storeDomainEntity->getDomainHost();
        }

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByTenantIdentifier($tenantRegistrationTransfer->getTenantNameOrFail())
            ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNULL)
            ->findOneOrCreate();
        $storeDomainEntity->setDomainHost($tenantRegistrationTransfer->getTenantNameOrFail());
        $storeDomainEntity->setData($storeDomainListData);
        $storeDomainEntity->save();

        $result
            ->setContext([
                'tenant_identifier' => $tenantRegistrationTransfer->getTenantNameOrFail(),
                'tenant_host' => implode(',', $createdHosts),
            ]);

        return $result;
    }

    protected function generateTenantHost(TenantTransfer $tenantTransfer, string $storeName): string
    {
        return strtolower($storeName) . '-' . $tenantTransfer->getTenantHost();
    }
}
