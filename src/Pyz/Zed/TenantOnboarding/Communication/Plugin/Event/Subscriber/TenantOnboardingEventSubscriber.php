<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Subscriber;

use Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantOnboardingListener;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

class TenantOnboardingEventSubscriber extends AbstractPlugin implements EventSubscriberInterface
{
    public function getSubscribedEvents(EventCollectionInterface $eventCollection)
    {
        $this->subscibeForDemoImport($eventCollection);
        $this->subscibeForFullImport($eventCollection);

        return $eventCollection;
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function subscibeForDemoImport(EventCollectionInterface $eventCollection): void
    {
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new TenantOnboardingListener(),
            0,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportStoreListener(),
            10,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function subscibeForFullImport(EventCollectionInterface $eventCollection): void
    {
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new TenantOnboardingListener(),
            0,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportStoreListener(),
            10,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCommerceListener(),
            9,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCatalogListener(),
            8,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportSpecialCatalogListener(),
            7,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMerchantListener(),
            6,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCmsListener(),
            5,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BListener(),
            4,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BProductListener(),
            3,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BProducStocktListener(),
            2,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMarketplaceListener(),
            1,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
    }
}
