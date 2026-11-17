<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Spryker\Shared\Event\EventConfig as SharedEventConfig;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\MessageAgeCalculator;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\MessageAgeCalculatorInterface;
use Spryker\Zed\Event\Business\Queue\Consumer\EventQueueConsumer;
use Throwable;

/**
 * Assembles one task-run record from the three hooks that observe a queue task.
 *
 * Process-global for the same reason as the listener collector: hooks have no constructor and no
 * container, so the only way the three callbacks can meet is a shared instance.
 */
class TaskRunCollector
{
    protected static ?self $instance = null;

    protected ?string $queueName = null;

    protected int $configuredChunkSize = 0;

    protected int $receivedMessageCount = 0;

    protected int $processedMessageCount = 0;

    protected int $receivedBytes = 0;

    protected float $processingSeconds = 0.0;

    /**
     * @var array<string, mixed>
     */
    protected array $ageHistogram = [];

    /**
     * @var array<string, mixed>
     */
    protected array $attemptAgeHistogram = [];

    /**
     * Null until the batch outcome was observed: a batch that threw has no outcome to count.
     */
    protected ?int $retriedMessageCount = null;

    protected ?int $failedAfterRetryMessageCount = null;

    protected ?int $routedToErrorMessageCount = null;

    protected ?string $exceptionClassName = null;

    /**
     * @var array<string, float|int|null>|null
     */
    protected ?array $breakdown = null;

    protected ?int $memoryStartBytes = null;

    protected ?int $memoryPeakBytes = null;

    /**
     * @var array<string, float>|null
     */
    protected ?array $cpu = null;

    protected MessageAgeCalculatorInterface $messageAgeCalculator;

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\MessageAgeCalculatorInterface|null $messageAgeCalculator
     */
    final public function __construct(?MessageAgeCalculatorInterface $messageAgeCalculator = null)
    {
        $this->messageAgeCalculator = $messageAgeCalculator ?? new MessageAgeCalculator();
    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * @param string $queueName
     *
     * @return void
     */
    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    /**
     * @param int $configuredChunkSize
     *
     * @return void
     */
    public function setConfiguredChunkSize(int $configuredChunkSize): void
    {
        $this->configuredChunkSize = $configuredChunkSize;
    }

    /**
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return void
     */
    public function beforeProcessing(array $queueReceiveMessageTransfers): void
    {
        $this->receivedMessageCount = count($queueReceiveMessageTransfers);
        $this->ageHistogram = $this->messageAgeCalculator->calculateHistogram($queueReceiveMessageTransfers);
        $this->attemptAgeHistogram = $this->messageAgeCalculator->calculateAttemptHistogram($queueReceiveMessageTransfers);
        $this->receivedBytes = $this->sumBodyBytes($queueReceiveMessageTransfers);
    }

    /**
     * @param int $processedMessageCount
     * @param float $processingSeconds
     *
     * @return void
     */
    public function afterProcessing(int $processedMessageCount, float $processingSeconds): void
    {
        $this->processedMessageCount = $processedMessageCount;
        $this->processingSeconds = $processingSeconds;
    }

    /**
     * Classifies each message by the routing key the consumer left on it, which is what the queue
     * acts on after the batch: `retry` is republished to the retry queue, `error` to the error queue.
     * Reading the final state rather than counting calls into the consumer counts each message once,
     * even when it passes the failure path twice, and covers the synchronization consumers, which
     * route to `error` but have no retry path.
     *
     * @param array<mixed> $processedMessages
     *
     * @return void
     */
    public function recordOutcome(array $processedMessages): void
    {
        $this->retriedMessageCount = 0;
        $this->failedAfterRetryMessageCount = 0;
        $this->routedToErrorMessageCount = 0;

        foreach ($processedMessages as $queueReceiveMessageTransfer) {
            if ($queueReceiveMessageTransfer instanceof QueueReceiveMessageTransfer) {
                $this->countOutcome($queueReceiveMessageTransfer);
            }
        }
    }

    /**
     * @param \Throwable $throwable
     *
     * @return void
     */
    public function recordThrowable(Throwable $throwable): void
    {
        $this->exceptionClassName = $throwable::class;
    }

    /**
     * @param array<string, float|int|null>|null $breakdown
     *
     * @return void
     */
    public function setBreakdown(?array $breakdown): void
    {
        $this->breakdown = $breakdown;
    }

    /**
     * @param int $memoryStartBytes
     *
     * @return void
     */
    public function setMemoryStart(int $memoryStartBytes): void
    {
        $this->memoryStartBytes = $memoryStartBytes;
    }

    /**
     * @param int $memoryPeakBytes The process peak from `MemoryTracker`, which listener scopes cannot lower.
     *
     * @return void
     */
    public function setMemoryPeak(int $memoryPeakBytes): void
    {
        $this->memoryPeakBytes = $memoryPeakBytes;
    }

    /**
     * @param array<string, float>|null $cpu The value `CpuTracker::endScope()` returned for the task run.
     *
     * @return void
     */
    public function setCpu(?array $cpu): void
    {
        $this->cpu = $cpu;
    }

    /**
     * The one place a task-run record is assembled; a new field is added here and nowhere else.
     *
     * @param float $totalSeconds
     *
     * @return array<string, mixed>
     */
    public function buildRecord(float $totalSeconds): array
    {
        $record = [
            'ts' => round(microtime(true) - $totalSeconds, 6),
            'queue' => $this->queueName ?? 'unknown',
            'pid' => getmypid(),
            'chunk_configured' => $this->configuredChunkSize,
            'chunk_received' => $this->receivedMessageCount,
            'processed' => $this->processedMessageCount,
            'failed' => max(0, $this->receivedMessageCount - $this->processedMessageCount),
            'ms_total' => round($totalSeconds * 1000, 3),
            'ms_process' => round($this->processingSeconds * 1000, 3),
            'peak_mb' => MemoryTracker::toMegabytes($this->memoryPeakBytes),
            'mem_start_mb' => MemoryTracker::toMegabytes($this->memoryStartBytes),
            'mem_peak_incr_mb' => MemoryTracker::toMegabytes($this->resolveMemoryPeakIncrementBytes()),
        ] + CpuTracker::toRecordFields($this->cpu);

        // Both rate directions: messages per second is the capacity of the queue, milliseconds per
        // message the cost of one, and under backlog they answer different questions.
        if ($this->processingSeconds > 0.0) {
            $record['msg_per_sec'] = round($this->receivedMessageCount / $this->processingSeconds, 3);
        }

        if ($this->receivedMessageCount > 0) {
            $record['ms_per_msg'] = round(
                ($this->processingSeconds * 1000) / $this->receivedMessageCount,
                4,
            );
            $record['bytes'] = $this->receivedBytes;
            $record['bytes_per_msg'] = (int)round($this->receivedBytes / $this->receivedMessageCount);
        }

        if ($this->configuredChunkSize > 0) {
            $record['fill_ratio'] = round($this->receivedMessageCount / $this->configuredChunkSize, 4);
        }

        if ($this->ageHistogram) {
            $record['age_hist'] = $this->ageHistogram;
        }

        $record['attempt_age_hist'] = $this->attemptAgeHistogram ?: null;
        $record['retried'] = $this->retriedMessageCount;
        $record['failed_after_retry'] = $this->failedAfterRetryMessageCount;
        $record['routed_error'] = $this->routedToErrorMessageCount;
        $record['exception'] = $this->exceptionClassName;
        $record['breakdown'] = $this->breakdown;

        return $record;
    }

    /**
     * @return bool
     */
    public function hasMessages(): bool
    {
        return $this->receivedMessageCount > 0;
    }

    /**
     * @return int|null
     */
    protected function resolveMemoryPeakIncrementBytes(): ?int
    {
        if ($this->memoryStartBytes === null || $this->memoryPeakBytes === null) {
            return null;
        }

        return max(0, $this->memoryPeakBytes - $this->memoryStartBytes);
    }

    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return void
     */
    protected function countOutcome(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        $routingKey = $queueReceiveMessageTransfer->getRoutingKey();

        if ($routingKey === SharedEventConfig::EVENT_ROUTING_KEY_RETRY) {
            $this->retriedMessageCount++;

            return;
        }

        // The synchronization consumers use the same `error` literal for their error routing.
        if ($routingKey !== SharedEventConfig::EVENT_ROUTING_KEY_ERROR) {
            return;
        }

        $this->routedToErrorMessageCount++;

        if ($this->getRetryCount($queueReceiveMessageTransfer) > 0) {
            $this->failedAfterRetryMessageCount++;
        }
    }

    /**
     * Decoded only for messages routed to the error queue, which are rare, so the cost stays off
     * the path every message takes.
     *
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return int
     */
    protected function getRetryCount(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): int
    {
        $body = json_decode((string)$queueReceiveMessageTransfer->getQueueMessage()?->getBody(), true);
        $retryCount = is_array($body) ? ($body[EventQueueConsumer::RETRY_KEY] ?? 0) : 0;

        return is_numeric($retryCount) ? (int)$retryCount : 0;
    }

    /**
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return int
     */
    protected function sumBodyBytes(array $queueReceiveMessageTransfers): int
    {
        $bytes = 0;

        foreach ($queueReceiveMessageTransfers as $queueReceiveMessageTransfer) {
            $queueSendMessageTransfer = $queueReceiveMessageTransfer->getQueueMessage();

            if ($queueSendMessageTransfer !== null) {
                $bytes += strlen((string)$queueSendMessageTransfer->getBody());
            }
        }

        return $bytes;
    }
}
