<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantOnboardingListener extends AbstractPlugin implements EventBulkHandlerInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName)
    {
        foreach ($eventEntityTransfers as $eventEntityTransfer) {
            $tenantOnboardingMessageTransfer = new TenantOnboardingMessageTransfer();
            $tenantOnboardingMessageTransfer->fromArray(json_decode($eventEntityTransfer->getBody(), true), true);

            $this->getFacade()->processOnboardingStep($tenantOnboardingMessageTransfer);
        }
    }
}
