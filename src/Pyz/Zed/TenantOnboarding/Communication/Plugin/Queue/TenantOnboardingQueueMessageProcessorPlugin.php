<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Queue;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Queue\Dependency\Plugin\QueueMessageProcessorPluginInterface;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantOnboardingQueueMessageProcessorPlugin extends AbstractPlugin implements QueueMessageProcessorPluginInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueMessageTransfers
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function processMessages(array $queueMessageTransfers): array
    {
        foreach ($queueMessageTransfers as $queueMessageTransfer) {
            $this->processMessage($queueMessageTransfer);
        }

        return $queueMessageTransfers;
    }

    /**
     * @return int
     */
    public function getChunkSize(): int
    {
        return 1;
    }

    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueMessageTransfer
     *
     * @return void
     */
    protected function processMessage(QueueReceiveMessageTransfer $queueMessageTransfer): void
    {
        try {
            $messageBody = $queueMessageTransfer->getQueueMessage()->getBody();

            $tenantOnboardingMessageTransfer = new TenantOnboardingMessageTransfer();
            $tenantOnboardingMessageTransfer->fromArray(json_decode($messageBody, true), true);

            $this->getFacade()->processOnboardingStep($tenantOnboardingMessageTransfer);

            $queueMessageTransfer->setAcknowledge(true);
        } catch (\Exception $exception) {
            $queueMessageTransfer->setReject(true);
            $queueMessageTransfer->setHasError(true);
        }
    }
}
