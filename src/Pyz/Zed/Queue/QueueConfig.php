<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Queue;

use Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Event\EventConstants;
use Spryker\Shared\Log\LogConstants;
use Pyz\Shared\Queue\QueueConstants;
use Spryker\Zed\Queue\QueueConfig as SprykerQueueConfig;

class QueueConfig extends SprykerQueueConfig
{
    /**
     * @var string
     */
    public const RABBITMQ = 'rabbitmq';

    public const QUEUE_WORKER_MAX_PROCESSES = 'QUEUE_WORKER_MAX_PROCESSES';

    /**
     * @return array<int>
     */
    public function getSignalsForGracefulWorkerShutdown(): array
    {
        return [
            static::SIGINT,
            static::SIGQUIT,
            static::SIGABRT,
            static::SIGTERM,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getQueueReceiverOptions(): array
    {
        return [
            QueueConstants::QUEUE_DEFAULT_RECEIVER => [
                static::RABBITMQ => $this->getRabbitMqQueueConsumerOptions(),
            ],
            EventConstants::EVENT_QUEUE => [
                static::RABBITMQ => $this->getRabbitMqQueueConsumerOptions(),
            ],
            Config::get(LogConstants::LOG_QUEUE_NAME) => [
                static::RABBITMQ => $this->getRabbitMqQueueConsumerOptions(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getMessageCheckOptions(): array
    {
        return [
            QueueConstants::QUEUE_WORKER_MESSAGE_CHECK_OPTION => [
                static::RABBITMQ => $this->getRabbitMqQueueMessageCheckOptions(),
            ],
        ];
    }

    /**
     * @return \Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer
     */
    protected function getRabbitMqQueueMessageCheckOptions(): RabbitMqConsumerOptionTransfer
    {
        $queueOptionTransfer = $this->getRabbitMqQueueConsumerOptions();
        $queueOptionTransfer->setRequeueOnReject(true);

        return $queueOptionTransfer;
    }

    /**
     * @return \Generated\Shared\Transfer\RabbitMqConsumerOptionTransfer
     */
    protected function getRabbitMqQueueConsumerOptions(): RabbitMqConsumerOptionTransfer
    {
        $queueOptionTransfer = new RabbitMqConsumerOptionTransfer();
        $queueOptionTransfer->setConsumerExclusive(false);
        $queueOptionTransfer->setNoWait(false);

        return $queueOptionTransfer;
    }

    public function getQueueWorkerMaxProcesses(): int
    {
        return $this->get(QueueConstants::QUEUE_WORKER_MAX_PROCESSES, 5);
    }

    public function shouldIgnoreNotDetectedFreeMemory(): bool
    {
        return $this->get(QueueConstants::QUEUE_WORKER_IGNORE_MEM_READ_FAILURE, false);
    }

    /**
     * @return int
     */
    public function getFreeMemoryBuffer(): int
    {
        return $this->get(QueueConstants::QUEUE_WORKER_FREE_MEMORY_BUFFER, 250);
    }

    /**
     * @return int
     */
    public function memoryReadProcessTimeout(): int
    {
        return $this->get(QueueConstants::QUEUE_WORKER_MEMORY_READ_PROCESS_TIMEOUT, 5);
    }
}
