<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Strategy;

use ArrayObject;
use Generated\Shared\Transfer\QueueTransfer;
use Psr\Log\LoggerInterface;
use Pyz\Client\RabbitMq\RabbitMqClientInterface;

trait ScanQueuesTrait
{
    /**
     * @var \Pyz\Client\RabbitMq\RabbitMqClientInterface
     */
    protected RabbitMqClientInterface $mqClient;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    protected int $scanCount = 0;

    private float $lastScanAt = 0;

    private bool $lastScanWasEmpty = false;

    protected function scanQueues(array $queueNames, $emptyScanCooldownSeconds = 5): ArrayObject
    {
        $sinceLastScan = microtime(true) - $this->lastScanAt;
        $lastEmptyScanTimeoutPassed = $this->lastScanWasEmpty && ($sinceLastScan > $emptyScanCooldownSeconds);

        if (!$this->lastScanWasEmpty || $lastEmptyScanTimeoutPassed) {
            $queueList = $this->directScanQueues($queueNames);

            $this->lastScanAt = microtime(true);
            $this->lastScanWasEmpty = $queueList->count() === 0;

            return $queueList;
        }

        return new ArrayObject();
    }

    /**
     * @param array<string> $queueNames
     *
     * @return \ArrayObject<\Generated\Shared\Transfer\QueueTransfer>
     */
    protected function directScanQueues(array $queueNames): ArrayObject
    {
        $this->scanCount++;
        $this->logger->info(sprintf('> SCANNING QUEUES - %d...', $this->scanCount));

        $queuesPerStore = new ArrayObject();

        $qCount = 0;
        $fullQueues = 0;
        $msgCount = 0;

        foreach ($queueNames as $queueName) {
            $qCount++;

            $queueMessageCount = $this->mqClient->getQueueMetrics(
                $queueName,
            )['messageCount'] ?? 0;

            if ($queueMessageCount === 0) {
                continue;
            }

            $queuesPerStore->append(
                (new QueueTransfer())->setQueueName($queueName)
                    ->setMsgCount($queueMessageCount),
            );

            $msgCount += $queueMessageCount;
            $fullQueues += ($queueMessageCount > 0 ? 1 : 0);
        }

        $this->logger->info(sprintf(
            '> SCANNING %d DONE: %d / %d queues, %d messages total, %d msg/queue avg',
            $this->scanCount,
            $fullQueues,
            $qCount,
            $msgCount,
            floor($msgCount / $qCount),
        ));

        return $queuesPerStore;
    }
}
