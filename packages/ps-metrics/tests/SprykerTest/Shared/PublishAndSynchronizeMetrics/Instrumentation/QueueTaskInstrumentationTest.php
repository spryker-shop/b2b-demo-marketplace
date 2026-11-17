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
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\ListenerMetricsCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\TaskRunCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\QueueTaskInstrumentationProxy;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group QueueTaskInstrumentationTest
 * Add your own group annotations below this line
 */
class QueueTaskInstrumentationTest extends Unit
{
    protected string $outputFile;

    protected string|false $previousOutputFile;

    /**
     * @return void
     */
    protected function _before(): void
    {
        $this->outputFile = sprintf('%s/s4-task-runs-%s.jsonl', sys_get_temp_dir(), uniqid());
        $this->previousOutputFile = getenv(PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE);
        putenv(sprintf('%s=%s', PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE, $this->outputFile));
        TaskRunCollector::reset();
        ListenerMetricsCollector::reset();
    }

    /**
     * @return void
     */
    protected function _after(): void
    {
        putenv($this->previousOutputFile === false
            ? PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE
            : sprintf('%s=%s', PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE, $this->previousOutputFile));

        if (is_file($this->outputFile)) {
            unlink($this->outputFile);
        }

        TaskRunCollector::reset();
    }

    /**
     * The post hook of TaskManager::run() receives the throwable in place of a response; a batch that
     * threw is the run an operator most needs to see, so its record must not be dropped.
     *
     * @return void
     */
    public function testAThrowingBatchStillWritesItsTaskRunRecord(): void
    {
        // Arrange
        $exception = new RuntimeException('listener exploded');
        QueueTaskInstrumentationProxy::startTaskRun('event');
        QueueTaskInstrumentationProxy::startProcessing($this->createMessages(3));

        // Act
        QueueTaskInstrumentationProxy::endProcessing(null, $exception);
        QueueTaskInstrumentationProxy::endTaskRun($exception);

        // Assert
        $records = $this->readRecords();
        $this->assertCount(1, $records);
        $this->assertSame('event', $records[0]['queue']);
        $this->assertSame(3, $records[0]['chunk_received']);
        $this->assertSame(0, $records[0]['processed']);
        $this->assertSame(RuntimeException::class, $records[0]['exception']);
    }

    /**
     * Nothing was routed anywhere, so the retry counts were not observed: null, never 0.
     *
     * @return void
     */
    public function testAThrowingBatchReportsItsRetryCountsAsNullNotZero(): void
    {
        // Arrange
        $exception = new RuntimeException('listener exploded');
        QueueTaskInstrumentationProxy::startTaskRun('event');
        QueueTaskInstrumentationProxy::startProcessing($this->createMessages(2));

        // Act
        QueueTaskInstrumentationProxy::endProcessing(null, $exception);
        QueueTaskInstrumentationProxy::endTaskRun($exception);

        // Assert
        $record = $this->readRecords()[0];
        $this->assertNull($record['retried']);
        $this->assertNull($record['failed_after_retry']);
        $this->assertNull($record['routed_error']);
    }

    /**
     * @return void
     */
    public function testASuccessfulBatchWritesItsRetryCountsAndNoException(): void
    {
        // Arrange
        $messages = $this->createMessages(2);
        $messages[0]->setRoutingKey(SharedEventConfig::EVENT_ROUTING_KEY_RETRY);
        QueueTaskInstrumentationProxy::startTaskRun('event');
        QueueTaskInstrumentationProxy::startProcessing($messages);

        // Act
        QueueTaskInstrumentationProxy::endProcessing($messages, null);
        QueueTaskInstrumentationProxy::endTaskRun(null);

        // Assert
        $record = $this->readRecords()[0];
        $this->assertSame(1, $record['retried']);
        $this->assertSame(0, $record['failed_after_retry']);
        $this->assertNull($record['exception']);
    }

    /**
     * @return void
     */
    public function testAnIdlePollWritesNoRecord(): void
    {
        // Arrange
        QueueTaskInstrumentationProxy::startTaskRun('event');

        // Act
        QueueTaskInstrumentationProxy::endTaskRun(null);

        // Assert
        $this->assertSame([], $this->readRecords());
    }

    /**
     * @param int $messageCount
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function createMessages(int $messageCount): array
    {
        $messages = [];

        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = (new QueueReceiveMessageTransfer())
                ->setQueueMessage((new QueueSendMessageTransfer())->setBody('{}'));
        }

        return $messages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readRecords(): array
    {
        if (!is_file($this->outputFile)) {
            return [];
        }

        $records = [];

        foreach (file($this->outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $records[] = json_decode($line, true);
        }

        return $records;
    }
}
