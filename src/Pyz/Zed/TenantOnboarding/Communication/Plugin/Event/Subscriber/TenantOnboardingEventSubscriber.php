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
            9,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCommerceListener(),
            8,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCatalogListener(),
            7,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportSpecialCatalogListener(),
            6,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMerchantListener(),
            5,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportCmsListener(),
            4,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BListener(),
            3,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportB2BProductListener(),
            2,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );
        $eventCollection->addListenerQueued(
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT,
            new \Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener\TenantImportMarketplaceListener(),
            1,
            null,
            \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING,
        );

        return $eventCollection;
    }
}
