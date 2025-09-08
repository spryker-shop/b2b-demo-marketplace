<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\Queue;

use Spryker\Zed\Event\Communication\Plugin\Queue\EventQueueMessageProcessorPlugin;

class TenantOnboardingEventQueueMessageProcessorPlugin extends EventQueueMessageProcessorPlugin
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueMessageTransfers
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function processMessages(array $queueMessageTransfers)
    {
        return (new \Spryker\Zed\Event\Business\EventFacade())
            ->processEnqueuedMessages($queueMessageTransfers);
    }

    public function getChunkSize()
    {
        return 1;
    }
}
