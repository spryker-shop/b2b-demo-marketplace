<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use ArrayObject;
use Codeception\Test\Unit;
use ReflectionProperty;
use RuntimeException;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MemoryTracker;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SyncFlushCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SyncFlushInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\ScriptedCpuTracker;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\ScriptedMemoryTracker;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group SyncFlushCollectorTest
 * Add your own group annotations below this line
 */
class SyncFlushCollectorTest extends Unit
{
    /**
     * @var string
     */
    protected const string SYNCHRONIZER_CLASS = 'Spryker\Zed\Synchronization\Business\Synchronizer\InMemoryMessageSynchronizer';

    /**
     * @var int
     */
    protected const int MEGABYTE = 1048576;

    /**
     * @var list<string>
     */
    protected const array C6_C7_AND_C8_FIELDS = ['kind', 'ts', 'pid', 'context', 'queue', 'command', 'ms', 'messages', 'breakdown', 'error', 'peak_mb', 'mem_start_mb', 'mem_peak_incr_mb', 'cpu_user_ms', 'cpu_sys_ms'];

    /**
     * @var \ArrayObject<int, array<string, mixed>>
     */
    protected ArrayObject $writtenRecords;

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        SyncFlushCollector::reset();
        BreakdownCollector::reset();
        MemoryTracker::reset();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testWritesTheRecordWhenTheFlushReturns(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->openFlush(12);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertCount(1, $this->writtenRecords);
        $this->assertSame(static::C6_C7_AND_C8_FIELDS, array_keys($this->writtenRecords[0]));
        $this->assertSame(SyncFlushCollector::KIND, $this->writtenRecords[0]['kind']);
        $this->assertSame(12, $this->writtenRecords[0]['messages']);
        $this->assertNull($this->writtenRecords[0]['error']);
    }

    /**
     * @return void
     */
    public function testFlushRecordCarriesTheThreeMemoryFields(): void
    {
        // Arrange
        $memoryTracker = new ScriptedMemoryTracker();
        $memoryTracker->allocate(64 * static::MEGABYTE, 0);
        $memoryTracker->allocate(-24 * static::MEGABYTE, 0);
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false), $memoryTracker);
        $syncFlushCollector->openFlush(3);
        $memoryTracker->allocate(6 * static::MEGABYTE, 0);

        // Act
        $record = $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertNotNull($record);
        $this->assertSame(64.0, $record['peak_mb']);
        $this->assertSame(40.0, $record['mem_start_mb']);
        $this->assertSame(6.0, $record['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testFlushCpuFieldsAreTheCpuSpentDuringTheFlush(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false), null, $cpuTracker);
        $syncFlushCollector->openFlush(3);
        $cpuTracker->spend(7000, 3000);

        // Act
        $record = $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertNotNull($record);
        $this->assertSame(7.0, $record['cpu_user_ms']);
        $this->assertSame(3.0, $record['cpu_sys_ms']);
    }

    /**
     * @return void
     */
    public function testFlushCpuFieldsAreNullNotZeroWhenCpuCannotBeRead(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $cpuTracker->isReadable = false;
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false), null, $cpuTracker);
        $syncFlushCollector->openFlush(3);

        // Act
        $record = $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertNotNull($record);
        $this->assertNull($record['cpu_user_ms']);
        $this->assertNull($record['cpu_sys_ms']);
    }

    /**
     * @return void
     */
    public function testFlushMemoryFieldsAreNullWhenTheFlushWasNotMeasured(): void
    {
        // Arrange
        $memoryTracker = new class extends ScriptedMemoryTracker {
            /**
             * @return bool
             */
            public function canMeasureScopes(): bool
            {
                return false;
            }
        };
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false), $memoryTracker);
        $syncFlushCollector->openFlush(3);

        // Act
        $record = $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertNotNull($record);
        $this->assertNull($record['mem_start_mb']);
        $this->assertNull($record['mem_peak_incr_mb']);
    }

    /**
     * @return void
     */
    public function testWritesNothingWhenNoFlushIsOpen(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));

        // Act
        $record = $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertNull($record);
        $this->assertCount(0, $this->writtenRecords);
    }

    /**
     * Every console process ends with a flush, and an empty one does no I/O.
     *
     * @return void
     */
    public function testWritesNothingForAFlushOfAnEmptyBuffer(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->openFlush(0);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertCount(0, $this->writtenRecords);
    }

    /**
     * @return void
     */
    public function testWritesTheExceptionClassOfAFlushThatThrew(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->openFlush(0);

        // Act
        $syncFlushCollector->closeFlush(new RuntimeException('down'));

        // Assert
        $this->assertSame(RuntimeException::class, $this->writtenRecords[0]['error']);
    }

    /**
     * @return void
     */
    public function testWritesNullNotZeroMessagesWhenTheBufferCouldNotBeRead(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->openFlush(null);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertCount(1, $this->writtenRecords);
        $this->assertNull($this->writtenRecords[0]['messages']);
    }

    /**
     * @return void
     */
    public function testWritesTheRecordWithANullBreakdownWhenTheBreakdownIsOff(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->openFlush(3);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertCount(1, $this->writtenRecords);
        $this->assertNull($this->writtenRecords[0]['breakdown']);
    }

    /**
     * @return void
     */
    public function testAttributesCallsMadeDuringTheFlushToTheFlushBreakdown(): void
    {
        // Arrange
        $breakdownCollector = $this->createBreakdownCollector(true);
        $syncFlushCollector = $this->createCollector($breakdownCollector);
        $breakdownCollector->beginCall(BreakdownCollector::CATEGORY_KV);
        $breakdownCollector->endCall(BreakdownCollector::CATEGORY_KV);
        $syncFlushCollector->openFlush(3);
        $breakdownCollector->beginCall(BreakdownCollector::CATEGORY_KV);
        $breakdownCollector->endCall(BreakdownCollector::CATEGORY_KV);
        $breakdownCollector->beginCall(BreakdownCollector::CATEGORY_SEARCH);
        $breakdownCollector->endCall(BreakdownCollector::CATEGORY_SEARCH);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $breakdown = $this->writtenRecords[0]['breakdown'];
        $this->assertSame(1, $breakdown['kv_calls']);
        $this->assertSame(1, $breakdown['search_calls']);
        $this->assertSame(0, $breakdown['db_calls']);
    }

    /**
     * @return void
     */
    public function testResolvesTheQueueContextWhenATaskRanInTheProcess(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->setQueueName('publish');
        $syncFlushCollector->markImporterRun();
        $syncFlushCollector->openFlush(1);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertSame(SyncFlushCollector::CONTEXT_QUEUE, $this->writtenRecords[0]['context']);
        $this->assertSame('publish', $this->writtenRecords[0]['queue']);
    }

    /**
     * @return void
     */
    public function testResolvesTheImportContextWhenAnImporterRanInTheProcess(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));

        // Act
        $syncFlushCollector->markImporterRun();

        // Assert
        $this->assertSame(SyncFlushCollector::CONTEXT_IMPORT, $syncFlushCollector->resolveContext());
    }

    /**
     * @return void
     */
    public function testResolvesTheImportContextFromADataImportCommandName(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));

        // Act
        $syncFlushCollector->setCommandName('data:import:glossary');

        // Assert
        $this->assertSame(SyncFlushCollector::CONTEXT_IMPORT, $syncFlushCollector->resolveContext());
    }

    /**
     * @return void
     */
    public function testResolvesTheOtherContextWithNullQueueWhenNeitherATaskNorAnImportRan(): void
    {
        // Arrange
        $syncFlushCollector = $this->createCollector($this->createBreakdownCollector(false));
        $syncFlushCollector->setCommandName('publish:trigger-events');
        $syncFlushCollector->openFlush(1);

        // Act
        $syncFlushCollector->closeFlush(null);

        // Assert
        $this->assertSame(SyncFlushCollector::CONTEXT_OTHER, $this->writtenRecords[0]['context']);
        $this->assertNull($this->writtenRecords[0]['queue']);
        $this->assertSame('publish:trigger-events', $this->writtenRecords[0]['command']);
    }

    /**
     * Reads the real synchronizer's buffer, so a renamed property shows up here and not as a silent null.
     *
     * @return void
     */
    public function testCountsEveryBufferedMessageAcrossDestinationsQueuesAndOperations(): void
    {
        // Arrange
        if (!class_exists(static::SYNCHRONIZER_CLASS)) {
            $this->markTestSkipped('The synchronization module is not installed.');
        }

        $buffer = new ReflectionProperty(static::SYNCHRONIZER_CLASS, 'messages');
        $original = $buffer->getValue();
        $buffer->setValue(null, [
            'storage' => ['sync.storage.product' => ['write' => ['a', 'b'], 'delete' => ['c']]],
            'search' => ['sync.search.product' => ['write' => ['d']]],
        ]);

        // Act
        $count = $this->createInstrumentationProxy()::countBufferedMessagesForTest();
        $buffer->setValue(null, $original);

        // Assert
        $this->assertSame(4, $count);
    }

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector $breakdownCollector
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MemoryTracker|null $memoryTracker
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker|null $cpuTracker
     *
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SyncFlushCollector
     */
    protected function createCollector(
        BreakdownCollector $breakdownCollector,
        ?MemoryTracker $memoryTracker = null,
        ?CpuTracker $cpuTracker = null
    ): SyncFlushCollector {
        $writtenRecords = new ArrayObject();
        $this->writtenRecords = $writtenRecords;
        $writer = new class ($writtenRecords) implements TaskRunMetricsWriterInterface {
            /**
             * @param \ArrayObject<int, array<string, mixed>> $records
             */
            public function __construct(protected ArrayObject $records)
            {
            }

            /**
             * @param array<string, mixed> $record
             *
             * @return void
             */
            public function write(array $record): void
            {
                $this->records[] = $record;
            }
        };

        return new SyncFlushCollector($writer, $breakdownCollector, $memoryTracker, $cpuTracker);
    }

    /**
     * @param bool $isEnabled
     *
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector
     */
    protected function createBreakdownCollector(bool $isEnabled): BreakdownCollector
    {
        $breakdownCollector = new BreakdownCollector();

        foreach ([BreakdownCollector::CATEGORY_DB, BreakdownCollector::CATEGORY_KV, BreakdownCollector::CATEGORY_SEARCH, BreakdownCollector::CATEGORY_BROKER] as $category) {
            $breakdownCollector->markInstalled($category);
        }

        if ($isEnabled) {
            $breakdownCollector->enable();
        }

        return $breakdownCollector;
    }

    /**
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SyncFlushInstrumentation
     */
    protected function createInstrumentationProxy(): SyncFlushInstrumentation
    {
        return new class extends SyncFlushInstrumentation {
            /**
             * @return int|null
             */
            public static function countBufferedMessagesForTest(): ?int
            {
                return static::countBufferedMessages();
            }
        };
    }
}
