<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\Event\Subscriber;

use Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantOnboardingListener;
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
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new TenantOnboardingListener(),
            0,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportStoreListener(),
            10,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
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
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new TenantOnboardingListener(),
            0,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportStoreListener(),
            10,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCommerceListener(),
            9,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCatalogListener(),
            8,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportSpecialCatalogListener(),
            7,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMerchantListener(),
            6,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCmsListener(),
            5,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BListener(),
            4,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BProductListener(),
            3,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BProducStocktListener(),
            2,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA,
            new \Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMarketplaceListener(),
            1,
            null,
            \Go\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
    }
}
