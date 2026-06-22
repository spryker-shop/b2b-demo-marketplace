<?php

declare(strict_types=1);

namespace Pyz\Client\Queue;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Pyz\Shared\QueueTiming\QueueTimingConstants;
use Spryker\Client\Queue\QueueClient as SprykerQueueClient;

/**
 * Project override of the Queue client.
 *
 * Perf-testing only: stamps a publish timestamp header onto outgoing messages so
 * the consumer side can measure how long each message sat in its queue (dwell).
 * No-op unless PS_QUEUE_TIMING=1. See {@link \Pyz\Shared\QueueTiming\QueueTimingConstants}.
 */
class QueueClient extends SprykerQueueClient
{
    /**
     * @param string $queueName
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return void
     */
    public function sendMessage($queueName, QueueSendMessageTransfer $queueSendMessageTransfer)
    {
        $this->stampPublishTime($queueSendMessageTransfer);

        parent::sendMessage($queueName, $queueSendMessageTransfer);
    }

    /**
     * @param string $queueName
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $queueSendMessageTransfers
     *
     * @return void
     */
    public function sendMessages($queueName, array $queueSendMessageTransfers)
    {
        foreach ($queueSendMessageTransfers as $queueSendMessageTransfer) {
            $this->stampPublishTime($queueSendMessageTransfer);
        }

        parent::sendMessages($queueName, $queueSendMessageTransfers);
    }

    /**
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return void
     */
    protected function stampPublishTime(QueueSendMessageTransfer $queueSendMessageTransfer): void
    {
        if (!QueueTimingConstants::isEnabled()) {
            return;
        }

        $headers = $queueSendMessageTransfer->getHeaders() ?? [];
        // Don't overwrite an existing stamp (message re-published across hops keeps origin time
        // per hop because each hop sends a fresh transfer; this guard is just defensive).
        $headers[QueueTimingConstants::HEADER_PUBLISHED_MS] = QueueTimingConstants::nowMs();
        $queueSendMessageTransfer->setHeaders($headers);
    }
}
