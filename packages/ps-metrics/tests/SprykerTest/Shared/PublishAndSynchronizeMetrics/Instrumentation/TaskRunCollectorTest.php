<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use RuntimeException;
use Spryker\Shared\Event\EventConfig as SharedEventConfig;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\TaskRunCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group TaskRunCollectorTest
 * Add your own group annotations below this line
 */
class TaskRunCollectorTest extends Unit
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        TaskRunCollector::reset();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testReportsNoMessagesBeforeABatchIsObserved(): void
    {
        // Act
        $taskRunCollector = new TaskRunCollector();

        // Assert
        $this->assertFalse($taskRunCollector->hasMessages());
    }

    /**
     * @return void
     */
    public function testReportsMessagesOnceABatchIsObserved(): void
    {
        // Arrange
        $taskRunCollector = new TaskRunCollector();

        // Act
        $taskRunCollector->beforeProcessing([$this->createMessage('{}')]);

        // Assert
        $this->assertTrue($taskRunCollector->hasMessages());
    }

    /**
     * @return void
     */
    public function testCountsMessagesThatFailedAsReceivedMinusProcessed(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(40, 1.0);

        // Act
        $taskRunCollector->afterProcessing(38, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertSame(40, $record['chunk_received']);
        $this->assertSame(38, $record['processed']);
        $this->assertSame(2, $record['failed']);
    }

    /**
     * @return void
     */
    public function testNeverReportsANegativeFailureCount(): void
    {
        // Arrange
        // A processor may report more handled messages than the batch held, and a negative count
        // would be read downstream as a surplus rather than as the nonsense it is.
        $taskRunCollector = $this->createCollectorWithBatch(10, 1.0);

        // Act
        $taskRunCollector->afterProcessing(12, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertSame(0, $record['failed']);
    }

    /**
     * @return void
     */
    public function testRecordsBothRateDirections(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(100, 1.0);

        // Act
        $taskRunCollector->afterProcessing(100, 2.0);
        $record = $taskRunCollector->buildRecord(4.0);

        // Assert
        $this->assertSame(50.0, $record['msg_per_sec']);
        $this->assertSame(20.0, $record['ms_per_msg']);
    }

    /**
     * @return void
     */
    public function testSeparatesProcessingTimeFromTotalTaskTime(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(5, 1.0);

        // Act
        $taskRunCollector->afterProcessing(5, 0.25);
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        // The gap between the two is the per-run overhead the batch size amortises.
        $this->assertSame(1000.0, $record['ms_total']);
        $this->assertSame(250.0, $record['ms_process']);
    }

    /**
     * @return void
     */
    public function testReportsFillRatioAgainstTheConfiguredChunkSize(): void
    {
        // Arrange
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->setConfiguredChunkSize(100);
        $taskRunCollector->beforeProcessing($this->createMessages(40, '{}'));

        // Act
        $taskRunCollector->afterProcessing(40, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertSame(0.4, $record['fill_ratio']);
    }

    /**
     * @return void
     */
    public function testOmitsFillRatioWhenNoChunkSizeWasObserved(): void
    {
        // Arrange
        // The chunk-size hook may not have fired; an invented denominator would report a full batch
        // as a starved one.
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($this->createMessages(40, '{}'));

        // Act
        $taskRunCollector->afterProcessing(40, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertArrayNotHasKey('fill_ratio', $record);
    }

    /**
     * @return void
     */
    public function testSumsMessageBodyBytesAndAveragesThemPerMessage(): void
    {
        // Arrange
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($this->createMessages(4, str_repeat('x', 100)));

        // Act
        $taskRunCollector->afterProcessing(4, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertSame(400, $record['bytes']);
        $this->assertSame(100, $record['bytes_per_msg']);
    }

    /**
     * @return void
     */
    public function testOmitsPerMessageFiguresForAnIdlePoll(): void
    {
        // Arrange
        // A poll that returned nothing has no per-message cost, and a zero would drag any average
        // over the run towards zero.
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->setConfiguredChunkSize(100);

        // Act
        $record = $taskRunCollector->buildRecord(0.5);

        // Assert
        $this->assertArrayNotHasKey('ms_per_msg', $record);
        $this->assertArrayNotHasKey('bytes_per_msg', $record);
        $this->assertArrayNotHasKey('msg_per_sec', $record);
    }

    /**
     * @return void
     */
    public function testRecordsTheQueueTheRunBelongsTo(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);

        // Act
        $taskRunCollector->setQueueName('sync.storage.product');
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame('sync.storage.product', $record['queue']);
    }

    /**
     * @return void
     */
    public function testFallsBackToAPlaceholderQueueNameRatherThanOmittingTheField(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        // Every record must be groupable by queue, so the field is always present.
        $this->assertSame('unknown', $record['queue']);
    }

    /**
     * @return void
     */
    public function testAttachesTheMessageAgeHistogramWhenMessagesCarryAPublishTimestamp(): void
    {
        // Arrange
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($this->createStampedMessages(6, 0.2));

        // Act
        $taskRunCollector->afterProcessing(6, 1.0);
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertArrayHasKey('age_hist', $record);
        $this->assertSame(6, $record['age_hist']['n']);
    }

    /**
     * @return void
     */
    public function testOmitsTheHistogramWhenNoMessageCarriesAPublishTimestamp(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(6, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(2.0);

        // Assert
        $this->assertArrayNotHasKey('age_hist', $record);
    }

    /**
     * @return void
     */
    public function testHandsOutOneSharedInstanceSoSeparateHooksMeet(): void
    {
        // Act
        $first = TaskRunCollector::getInstance();
        $second = TaskRunCollector::getInstance();

        // Assert
        $this->assertSame($first, $second);
    }

    /**
     * @return void
     */
    public function testResetDropsTheSharedInstance(): void
    {
        // Arrange
        $first = TaskRunCollector::getInstance();

        // Act
        TaskRunCollector::reset();

        // Assert
        $this->assertNotSame($first, TaskRunCollector::getInstance());
    }

    /**
     * @return void
     */
    public function testReportsTheAttemptAgeHistogramAsNullNotEmptyWhenNoMessageCarriesAnAttemptStamp(): void
    {
        // Arrange
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($this->createStampedMessages(3, 0.1));

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertArrayHasKey('attempt_age_hist', $record);
        $this->assertNull($record['attempt_age_hist']);
    }

    /**
     * @return void
     */
    public function testAttachesTheAttemptAgeHistogramForMessagesThatWereRetried(): void
    {
        // Arrange
        $messages = $this->createMessages(3, '{}');
        $messages[0]->getQueueMessage()->setBody((string)json_encode([
            PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT => sprintf('%.6F', microtime(true) - 0.1),
        ]));
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($messages);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(1, $record['attempt_age_hist']['n']);
    }

    /**
     * @return void
     */
    public function testReportsTheBreakdownAsNullNotZeroWhenTheBreakdownIsOff(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(4, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertArrayHasKey('breakdown', $record);
        $this->assertNull($record['breakdown']);
    }

    /**
     * @return void
     */
    public function testWritesTheBreakdownItWasGivenIntoTheRecord(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(4, 1.0);
        $breakdown = ['db_ms' => 12.5, 'db_calls' => 3, 'kv_ms' => null, 'kv_calls' => null];

        // Act
        $taskRunCollector->setBreakdown($breakdown);
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame($breakdown, $record['breakdown']);
    }

    /**
     * @return void
     */
    public function testReportsRetryCountsAsNullNotZeroWhenTheBatchOutcomeWasNotObserved(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(4, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertNull($record['retried']);
        $this->assertNull($record['failed_after_retry']);
        $this->assertNull($record['routed_error']);
    }

    /**
     * @return void
     */
    public function testCountsMessagesTheConsumerSentBackForRetry(): void
    {
        // Arrange
        $messages = $this->createMessages(5, '{}');
        $messages[1]->setRoutingKey(SharedEventConfig::EVENT_ROUTING_KEY_RETRY);
        $messages[3]->setRoutingKey(SharedEventConfig::EVENT_ROUTING_KEY_RETRY);
        $taskRunCollector = $this->createCollectorWithBatch(5, 1.0);

        // Act
        $taskRunCollector->recordOutcome($messages);
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(2, $record['retried']);
        $this->assertSame(0, $record['failed_after_retry']);
        $this->assertSame(0, $record['routed_error']);
    }

    /**
     * A message routed to the error queue on its first attempt (invalid body, no retry queue, or a
     * synchronization failure) did not exhaust any retries, so it is an error but not a retry failure.
     *
     * @return void
     */
    public function testCountsFailedAfterRetryOnlyForErrorRoutedMessagesThatWereRetriedBefore(): void
    {
        // Arrange
        $messages = [
            $this->createMessage('{"retry":2,"errorMessage":"boom"}'),
            $this->createMessage('{"errorMessage":"boom"}'),
            $this->createMessage('{"retry":0,"errorMessage":"boom"}'),
        ];

        foreach ($messages as $message) {
            $message->setRoutingKey(SharedEventConfig::EVENT_ROUTING_KEY_ERROR);
        }

        $taskRunCollector = $this->createCollectorWithBatch(3, 1.0);

        // Act
        $taskRunCollector->recordOutcome($messages);
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(1, $record['failed_after_retry']);
        $this->assertSame(3, $record['routed_error']);
        $this->assertSame(0, $record['retried']);
    }

    /**
     * @return void
     */
    public function testRecordsTheExceptionClassOfAThrowingBatch(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(2, 1.0);

        // Act
        $taskRunCollector->recordThrowable(new RuntimeException('boom'));
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(RuntimeException::class, $record['exception']);
    }

    /**
     * @return void
     */
    public function testReportsNoExceptionForABatchThatDidNotThrow(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(2, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertArrayHasKey('exception', $record);
        $this->assertNull($record['exception']);
    }

    /**
     * @return void
     */
    public function testTaskRunMemoryFieldsAreNullWhenNotMeasured(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertNull($record['peak_mb']);
        $this->assertNull($record['mem_start_mb']);
        $this->assertNull($record['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testTaskRunMemoryIncrementIsThePeakMinusTheMemoryAtStart(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);
        $taskRunCollector->setMemoryStart(40 * 1048576);
        $taskRunCollector->setMemoryPeak(46 * 1048576);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(46.0, $record['peak_mb']);
        $this->assertSame(40.0, $record['mem_start_mb']);
        $this->assertSame(6.0, $record['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testTaskRunCpuFieldsAreNullNotZeroWhenNotMeasured(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertArrayHasKey('cpu_user_ms', $record);
        $this->assertNull($record['cpu_user_ms']);
        $this->assertNull($record['cpu_sys_ms']);
    }

    /**
     * @return void
     */
    public function testTaskRunCpuFieldsArePresentAndNonNegativeForARealScope(): void
    {
        // Arrange
        $taskRunCollector = $this->createCollectorWithBatch(1, 1.0);
        $cpuTracker = new CpuTracker();
        $taskRunCollector->setCpu($cpuTracker->endScope($cpuTracker->beginScope()));

        // Act
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertIsFloat($record['cpu_user_ms']);
        $this->assertIsFloat($record['cpu_sys_ms']);
        $this->assertGreaterThanOrEqual(0.0, $record['cpu_user_ms']);
        $this->assertGreaterThanOrEqual(0.0, $record['cpu_sys_ms']);
    }

    /**
     * @param int $messageCount
     * @param float $processingSeconds
     *
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\TaskRunCollector
     */
    protected function createCollectorWithBatch(int $messageCount, float $processingSeconds): TaskRunCollector
    {
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing($this->createMessages($messageCount, '{}'));
        $taskRunCollector->afterProcessing($messageCount, $processingSeconds);

        return $taskRunCollector;
    }

    /**
     * @param int $messageCount
     * @param string $body
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function createMessages(int $messageCount, string $body): array
    {
        $messages = [];

        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = $this->createMessage($body);
        }

        return $messages;
    }

    /**
     * @param int $messageCount
     * @param float $ageSeconds
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function createStampedMessages(int $messageCount, float $ageSeconds): array
    {
        $messages = [];

        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = $this->createMessage('{}', microtime(true) - $ageSeconds);
        }

        return $messages;
    }

    /**
     * @param string $body
     * @param float|null $enqueuedAt
     *
     * @return \Generated\Shared\Transfer\QueueReceiveMessageTransfer
     */
    protected function createMessage(string $body, ?float $enqueuedAt = null): QueueReceiveMessageTransfer
    {
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody($body);

        if ($enqueuedAt !== null) {
            $queueSendMessageTransfer->setHeaders([
                PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => sprintf('%.6F', $enqueuedAt),
            ]);
        }

        return (new QueueReceiveMessageTransfer())->setQueueMessage($queueSendMessageTransfer);
    }
}
