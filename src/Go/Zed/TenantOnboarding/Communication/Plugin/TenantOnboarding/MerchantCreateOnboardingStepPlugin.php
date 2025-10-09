<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\UserCriteriaTransfer;
use Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Merchant\Dependency\MerchantEvents;
use Spryker\Zed\Url\Dependency\UrlEvents;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class MerchantCreateOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    public function getName(): string
    {
        return 'MerchantCreate';
    }

    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true);

        $tenantIdentifier = $tenantRegistrationTransfer->getTenantName();
        $merchantFacade = $this->getFactory()->getLocator()->merchant()->facade();
        $storeFacade = $this->getFactory()->getLocator()->store()->facade();
        $localeFacade = $this->getFactory()->getLocator()->locale()->facade();
        $eventFacade = $this->getFactory()->getLocator()->event()->facade();

        if ($merchantFacade->findOne((new MerchantCriteriaTransfer())->setMerchantReferences([$tenantIdentifier])) !== null) {
            return $result;
        }

        $merchantTransfer = (new \Generated\Shared\Transfer\MerchantTransfer())
            ->setMerchantReference($tenantIdentifier)
            ->setName($tenantRegistrationTransfer->getCompanyName())
            ->setEmail($tenantRegistrationTransfer->getEmail())
            ->setIsOpenForRelationRequest(false)
            ->setStatus('approved')
            ->setMerchantProfile(
                (new \Generated\Shared\Transfer\MerchantProfileTransfer())
                ->setContactPersonFirstName($tenantRegistrationTransfer->getCompanyName())
                ->setContactPersonLastName('Admin')
            )
            ->setIsActive(true);

        $storeRelationTransfer = new \Generated\Shared\Transfer\StoreRelationTransfer();
        $storeTransfers = $storeFacade->getAllStores();
        $storeRelationTransfer->setStores(new \ArrayObject($storeTransfers));
        $idStores = [];
        foreach ($storeRelationTransfer->getStores() as $store) {
            $idStores[] = $store->getIdStoreOrFail();
        }
        $storeRelationTransfer->setIdStores($idStores);
        $merchantTransfer->setStoreRelation($storeRelationTransfer);

        $locales = $localeFacade->getLocaleCollection();

        $urlTransfers = new \ArrayObject();
        foreach ($locales as $locale) {
            $localeName = explode('_', $locale->getLocaleName())[0];
            $urlTransfers[] = (new \Generated\Shared\Transfer\UrlTransfer())
                ->setFkLocale($locale->getIdLocale())
                ->setUrl(sprintf('/%s/merchant/%s', $localeName, $tenantIdentifier));
        }

        $merchantTransfer->setUrlCollection($urlTransfers);

        $merchantResponse = $merchantFacade->createMerchant($merchantTransfer);

        if (!$merchantResponse->getIsSuccess()) {
            return $result->setIsSuccessful(false);
        }

        $eventFacade->trigger(MerchantEvents::MERCHANT_PUBLISH, (new EventEntityTransfer())
            ->setId($merchantResponse->getMerchant()->getIdMerchantOrFail())
            ->setTenantReference($tenantIdentifier));

        $events = [];
        foreach ($merchantResponse->getMerchant()->getUrlCollection() as $urlTransfer) {
            $events[] = (new EventEntityTransfer())
                ->setId($urlTransfer->getIdUrlOrFail())
                ->setTenantReference($tenantIdentifier);
        }
        $eventFacade->triggerBulk(UrlEvents::URL_PUBLISH, $events);

        $merchantUserFacade = $this->getFactory()->getLocator()->merchantUser()->facade();

        $userCollectionTransfer = $this->getFactory()->getUserFacade()->getUserCollection(
            (new UserCriteriaTransfer())->setUserConditions(
                (new \Generated\Shared\Transfer\UserConditionsTransfer())
                    ->addUsername($tenantRegistrationTransfer->getEmailOrFail())
            )
        );

        if ($userCollectionTransfer->getUsers()->count() === 0) {
            return $result->setIsSuccessful(false);
        }

        $merchantUserTransfer = (new \Generated\Shared\Transfer\MerchantUserTransfer())
            ->setIdMerchant($merchantTransfer->getIdMerchantOrFail())
            ->setUser($userCollectionTransfer->getUsers()->offsetGet(0));

        $merchantUserResponseTransfer = $merchantUserFacade->createMerchantUser($merchantUserTransfer);
        if (!$merchantUserResponseTransfer->getIsSuccessful()) {
            return $result->setIsSuccessful(false);
        }

        return $result;
    }
}
