<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\ListenerMetricsCollector;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group ListenerMetricsCollectorTest
 * Add your own group annotations below this line
 */
class ListenerMetricsCollectorTest extends Unit
{
    /**
     * @var string
     */
    protected const string LISTENER = 'Spryker\Zed\CmsStorage\Communication\Plugin\CmsPageStoragePublishListener';

    /**
     * @var string
     */
    protected const string EVENT = 'Cms.version.publish';

    /**
     * @return void
     */
    protected function _before(): void
    {
        ListenerMetricsCollector::reset();
    }

    /**
     * @return void
     */
    public function testCollectsNothingBeforeAnythingIsRecorded(): void
    {
        // Assert
        $this->assertSame([], ListenerMetricsCollector::getInstance()->toArray());
    }

    /**
     * @return void
     */
    public function testKeepsOneRecordPerListenerAndEventPair(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 5], 10.0);
        $collector->record(static::LISTENER, ['Cms.other.publish' => 2], 4.0);

        // Assert
        $this->assertCount(2, $collector->toArray(), 'The same listener under two events stays separable.');
    }

    /**
     * @return void
     */
    public function testAccumulatesRepeatedCallsForTheSamePair(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 5], 10.0);
        $collector->record(static::LISTENER, [static::EVENT => 15], 30.0);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertSame(2, $record['calls']);
        $this->assertSame(20, $record['messages']);
        $this->assertSame(40.0, $record['ms']);
        $this->assertSame(2.0, $record['ms_per_msg'], 'Forty milliseconds over twenty messages.');
    }

    /**
     * @return void
     */
    public function testShortensTheListenerClassNameForReadability(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 1], 1.0);

        // Assert
        $this->assertSame('CmsPageStoragePublishListener', $collector->toArray()[0]['listener']);
    }

    /**
     * @return void
     */
    public function testDoesNotDivideByZeroWhenAGroupCarriedNoItems(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 0], 5.0);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertNull($record['ms_per_msg'], 'A cost per message is undefined without messages.');
        $this->assertSame([], $record['ms_per_msg_hist'], 'No observation means no histogram entry.');
    }

    /**
     * @return void
     */
    public function testRecordsOneHistogramObservationPerCallNotPerMessage(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 100], 50.0);

        // Assert
        $this->assertSame(
            1,
            $collector->toArray()[0]['ms_per_msg_hist']['n'],
            'The call is timed as a whole, so recording per message would invent observations.',
        );
    }

    /**
     * @return void
     */
    public function testReportsZeroErrorsForAListenerThatNeverFailed(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 3], 5.0);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertSame(0, $record['errors']);
        $this->assertSame(0, $record['failed_messages']);
    }

    /**
     * A bulk call that threw is one failed call; each item the per-item fallback then failed on is
     * another, so `errors` counts both while `failed_messages` counts only what left the listener.
     *
     * @return void
     */
    public function testCountsErrorsAsBulkCallsThatThrewPlusMessagesThatFailed(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();
        $collector->record(static::LISTENER, [static::EVENT => 3], 5.0);

        // Act
        $collector->recordBulkCallFailure(static::LISTENER, static::EVENT);
        $collector->recordMessageFailure(static::LISTENER, static::EVENT);
        $collector->recordMessageFailure(static::LISTENER, static::EVENT);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertSame(3, $record['errors']);
        $this->assertSame(2, $record['failed_messages']);
    }

    /**
     * @return void
     */
    public function testAttributesErrorsToTheirOwnListenerAndEventPair(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();
        $collector->record(static::LISTENER, [static::EVENT => 3, 'Cms.other.publish' => 1], 5.0);

        // Act
        $collector->recordMessageFailure(static::LISTENER, 'Cms.other.publish');

        // Assert
        $records = $collector->toArray();
        $this->assertSame(0, $records[0]['errors']);
        $this->assertSame(1, $records[1]['errors']);
    }

    /**
     * The single-handler path fails a message without ever reaching the timed bulk call.
     *
     * @return void
     */
    public function testKeepsARecordForAListenerThatOnlyEverFailed(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->recordMessageFailure(static::LISTENER, static::EVENT);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertSame(0, $record['calls']);
        $this->assertSame(1, $record['errors']);
        $this->assertNull($record['ms_per_msg']);
    }

    /**
     * @return void
     */
    public function testSumsTheBreakdownOfEveryCallOfTheSamePair(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 2], 10.0, ['db_ms' => 4.0, 'db_calls' => 2]);
        $collector->record(static::LISTENER, [static::EVENT => 2], 10.0, ['db_ms' => 1.5, 'db_calls' => 1]);

        // Assert
        $breakdown = $collector->toArray()[0]['breakdown'];
        $this->assertEqualsWithDelta(5.5, $breakdown['db_ms'], 0.0001);
        $this->assertSame(3, $breakdown['db_calls']);
    }

    /**
     * @return void
     */
    public function testReportsTheBreakdownAsNullNotZeroWhenNoCallCarriedOne(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 1], 3.0);

        // Assert
        $this->assertNull($collector->toArray()[0]['breakdown']);
    }

    /**
     * @return void
     */
    public function testListenerMemoryIncrementIsNullNotZeroWhenNoCallWasMeasured(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 4], 3.0);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertNull($record['mem_peak_incr_mb']);
        $this->assertNull($record['mem_peak_incr_hist']);
    }

    /**
     * @return void
     */
    public function testListenerMemoryIncrementIsNullForAListenerThatOnlyEverFailed(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->recordMessageFailure(static::LISTENER, static::EVENT);

        // Assert
        $this->assertNull($collector->toArray()[0]['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testListenerMemoryIncrementIsTheMaximumOverCalls(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0, null, 524288);
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0, null, 1048576);
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0, null, 262144);

        // Assert
        $this->assertSame(1.0, $collector->toArray()[0]['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testListenerMemoryIncrementOfZeroIsReportedAsZeroOnceMeasured(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 1], 1.0, null, 0);

        // Assert
        $this->assertSame(0.0, $collector->toArray()[0]['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testListenerMemoryHistogramIsKilobytesPerMessageAndSkipsCallsWithoutMessages(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 4], 1.0, null, 8192);
        $collector->record(static::LISTENER, [static::EVENT => 0], 1.0, null, 4096);

        // Assert
        $histogram = $collector->toArray()[0]['mem_peak_incr_hist'];
        $this->assertSame(1, $histogram['n']);
        $this->assertEqualsWithDelta(2.0, $histogram['sum'], 0.0001);
    }

    /**
     * @return void
     */
    public function testListenerCpuFieldsAreNullNotZeroWhenNoCallWasMeasured(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertArrayHasKey('cpu_user_ms', $record);
        $this->assertNull($record['cpu_user_ms']);
        $this->assertNull($record['cpu_sys_ms']);
    }

    /**
     * @return void
     */
    public function testListenerCpuFieldsAreTheSumOverCalls(): void
    {
        // Arrange
        $collector = ListenerMetricsCollector::getInstance();

        // Act
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0, null, null, [CpuTracker::FIELD_USER_MS => 1.25, CpuTracker::FIELD_SYS_MS => 0.5]);
        $collector->record(static::LISTENER, [static::EVENT => 2], 1.0, null, null, [CpuTracker::FIELD_USER_MS => 2.0, CpuTracker::FIELD_SYS_MS => 0.25]);

        // Assert
        $record = $collector->toArray()[0];
        $this->assertSame(3.25, $record['cpu_user_ms']);
        $this->assertSame(0.75, $record['cpu_sys_ms']);
    }
}
