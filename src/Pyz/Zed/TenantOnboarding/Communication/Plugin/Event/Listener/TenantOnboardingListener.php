<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantOnboardingListener extends AbstractPlugin implements EventHandlerInterface
{
    public function handle(TransferInterface $transfer, $eventName)
    {
        /** @var \Generated\Shared\Transfer\QueueSendMessageTransfer $transfer */
        $tenantOnboardingMessageTransfer = new TenantOnboardingMessageTransfer();
        $tenantOnboardingMessageTransfer->fromArray(json_decode($transfer->getBody(), true), true);

        $this->getFacade()->processOnboardingStep($tenantOnboardingMessageTransfer);
    }
}
