<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\DataImporterReportTransfer;
use RuntimeException;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\ImportRunCollector;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\ScriptedCpuTracker;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group ImportRunCollectorTest
 * Add your own group annotations below this line
 */
class ImportRunCollectorTest extends Unit
{
    /**
     * @var int
     */
    protected const int IMPORTER_ID = 7;

    /**
     * @var string
     */
    protected const string IMPORT_TYPE = 'product-stock';

    /**
     * @var array<string, string|false>
     */
    protected array $environmentBackup = [];

    /**
     * @var \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface&\ArrayAccess<int, array<string, mixed>>&\Countable
     */
    protected TaskRunMetricsWriterInterface $writer;

    /**
     * @return void
     */
    protected function _before(): void
    {
        $this->writer = $this->createInMemoryWriter();

        foreach ([PublishAndSynchronizeMetricsConstants::ENV_IMPORT_OUTPUT_FILE, PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE] as $name) {
            $this->environmentBackup[$name] = getenv($name);
        }
    }

    /**
     * @return void
     */
    protected function _after(): void
    {
        ImportRunCollector::reset();

        foreach ($this->environmentBackup as $name => $value) {
            putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
        }
    }

    /**
     * @return void
     */
    public function testWritesTheImporterRecordAsSoonAsTheImporterSpanClosesNotAtShutdown(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertCount(1, $this->writer, 'A killed import process never reaches shutdown, so the record must already be written.');
        $this->assertSame(ImportRunCollector::KIND_IMPORTER, $this->writer[0]['kind']);
    }

    /**
     * @return void
     */
    public function testWritesAMissingNativeImportTimeAsNullNeverAsZero(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport()->setImportTime(null), null);

        // Assert
        $this->assertArrayHasKey('ms_native', $this->writer[0]);
        $this->assertNull($this->writer[0]['ms_native']);
    }

    /**
     * @return void
     */
    public function testWritesExpectedAsNullWhenTheReaderIsNotCountable(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(
            static::IMPORTER_ID,
            $this->createReport()->setIsReaderCountable(false)->setExpectedImportableDataSetCount(0),
            null,
        );

        // Assert
        $this->assertArrayHasKey('expected', $this->writer[0]);
        $this->assertNull($this->writer[0]['expected']);
    }

    /**
     * @return void
     */
    public function testCopiesTheReportFieldsIntoTheImporterRecord(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, 'ignored-when-the-report-has-one');

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $record = $this->writer[0];
        $this->assertSame(static::IMPORT_TYPE, $record['import_type']);
        $this->assertSame('data/import/common/common/product_stock.csv', $record['source']);
        $this->assertSame(120, $record['expected']);
        $this->assertSame(118, $record['imported']);
        $this->assertTrue($record['is_success']);
        $this->assertIsFloat($record['ms_wall']);
        $this->assertIsFloat($record['peak_mb']);
    }

    /**
     * @return void
     */
    public function testKeepsTheNativeImportTimeAtFullFloatPrecision(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport()->setImportTime(0.0123456789), null);

        // Assert
        $this->assertEqualsWithDelta(12.3456789, $this->writer[0]['ms_native'], 1e-9, 'The console rounds to 0.01 s; the record must not.');
    }

    /**
     * @return void
     */
    public function testReportsAnImporterThatThrewAsFailedWithNullMeasurements(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, null, new RuntimeException('boom'));

        // Assert
        $record = $this->writer[0];
        $this->assertSame(static::IMPORT_TYPE, $record['import_type'], 'Falls back to the type read when the span opened.');
        $this->assertFalse($record['is_success']);
        $this->assertNull($record['imported']);
        $this->assertNull($record['ms_native']);
        $this->assertSame(RuntimeException::class, $record['error']);
    }

    /**
     * @return void
     */
    public function testRecordsOnlyTheOuterCallWhenAnImporterCallsParentImport(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertCount(1, $this->writer);
    }

    /**
     * @return void
     */
    public function testStampsEveryRecordWithKindPidAndTheStartOfTheSpan(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $before = microtime(true);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        usleep(20000);
        $collector->openPublish(1);

        // Act
        $collector->closePublish(null);
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $publishRecord = $this->writer[0];
        $importerRecord = $this->writer[1];
        $this->assertSame(getmypid(), $importerRecord['pid']);
        $this->assertGreaterThanOrEqual(round($before, 6), $importerRecord['ts']);
        $this->assertLessThan($publishRecord['ts'], $importerRecord['ts'], 'ts is when the span started, not when it was written.');
    }

    /**
     * @return void
     */
    public function testWritesThePublishRecordAsSoonAsTheFlushEnds(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsMessageCountingAvailable(true);
        $collector->openPublish(42);
        $collector->countEnqueuedMessages(40);
        $collector->countEnqueuedMessages(1);

        // Act
        $collector->closePublish(null);

        // Assert
        $record = $this->writer[0];
        $this->assertSame(ImportRunCollector::KIND_PUBLISH_EMIT, $record['kind']);
        $this->assertSame(42, $record['events']);
        $this->assertSame(41, $record['messages']);
        $this->assertIsFloat($record['ms']);
    }

    /**
     * @return void
     */
    public function testWritesMessagesAsNullNotZeroWhenNothingCouldCountThem(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openPublish(3);

        // Act
        $collector->closePublish(null);

        // Assert
        $this->assertArrayHasKey('messages', $this->writer[0]);
        $this->assertNull($this->writer[0]['messages']);
    }

    /**
     * @return void
     */
    public function testWritesAnUnreadableEventCountAsNull(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);

        // Act
        $collector->openPublish($collector->countBufferedEvents(null));
        $collector->closePublish(null);

        // Assert
        $this->assertArrayHasKey('events', $this->writer[0]);
        $this->assertNull($this->writer[0]['events']);
    }

    /**
     * @return void
     */
    public function testCountsBufferedEntityEventsAcrossEventNames(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);

        // Act
        $eventCount = $collector->countBufferedEvents([
            'Entity.spy_product.create' => [1 => true, 2 => true],
            'Entity.spy_stock_product.update' => [5 => true, 6 => true, 7 => true],
        ]);

        // Assert
        $this->assertSame(5, $eventCount);
    }

    /**
     * @return void
     */
    public function testIgnoresMessagesSentOutsideAPublishSpan(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsMessageCountingAvailable(true);
        $collector->countEnqueuedMessages(100);
        $collector->openPublish(0);

        // Act
        $collector->closePublish(null);

        // Assert
        $this->assertSame(0, $this->writer[0]['messages']);
    }

    /**
     * @return void
     */
    public function testAttributesAPublishToTheImporterItRanInsideOf(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->openPublish(1);
        $collector->closePublish(null);
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Act
        $collector->openPublish(1);
        $collector->closePublish(null);

        // Assert
        $this->assertSame(static::IMPORT_TYPE, $this->writer[0]['importer']);
        $this->assertNull($this->writer[2]['importer'], 'A collection after-import flush belongs to no importer.');
    }

    /**
     * @return void
     */
    public function testWritesTheCollectionRecordWithTheRequestedImportType(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openCollection('glossary');

        // Act
        $collector->closeCollection(null);

        // Assert
        $record = $this->writer[0];
        $this->assertSame(ImportRunCollector::KIND_COLLECTION, $record['kind']);
        $this->assertSame('glossary', $record['import_type']);
        $this->assertIsFloat($record['ms_wall']);
    }

    /**
     * @return void
     */
    public function testWritesNoRowsRecordUnlessRowsAreEnabled(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->recordDataSet(1.5);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertCount(1, $this->writer);
    }

    /**
     * @return void
     */
    public function testWritesOneRowsHistogramPerImporterWhenRowsAreEnabled(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsRowsEnabled(true);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->recordDataSet(1.5);
        $collector->recordDataSet(2.5);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $rowsRecord = $this->writer[1];
        $this->assertSame(ImportRunCollector::KIND_ROWS, $rowsRecord['kind']);
        $this->assertSame(static::IMPORT_TYPE, $rowsRecord['import_type']);
        $this->assertSame(2, $rowsRecord['n']);
        $this->assertSame(2, $rowsRecord['hist']['n']);
        $this->assertSame(2, $rowsRecord['hist']['format']);
    }

    /**
     * @return void
     */
    public function testWritesAnEmptyRowsHistogramAsNull(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsRowsEnabled(true);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertSame(0, $this->writer[1]['n']);
        $this->assertNull($this->writer[1]['hist']);
    }

    /**
     * @return void
     */
    public function testCountsATransactionAwareBrokerCallingParentExecuteAsOneDataSet(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsRowsEnabled(true);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->openDataSet();
        $collector->openDataSet();
        $collector->closeDataSet();
        $collector->closeDataSet();

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertSame(1, $this->writer[1]['n']);
    }

    /**
     * @return void
     */
    public function testKeepsRowsWithTheRecordedImporterWhenANestedParentCallIsOpen(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->setIsRowsEnabled(true);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $collector->recordDataSet(1.0);
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertSame(1, $this->writer[1]['n']);
    }

    /**
     * @return void
     */
    public function testWritesImportRecordsBesideTheTaskRunFileByDefault(): void
    {
        // Arrange
        putenv(PublishAndSynchronizeMetricsConstants::ENV_IMPORT_OUTPUT_FILE);
        putenv(sprintf('%s=/data/metrics/run-1/task-runs.jsonl', PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE));

        // Act
        $path = ImportRunCollector::resolveOutputFilePath();

        // Assert
        $this->assertSame('/data/metrics/run-1/import-runs.jsonl', $path);
    }

    /**
     * @return void
     */
    public function testHonoursAnExplicitImportOutputFile(): void
    {
        // Arrange
        putenv(sprintf('%s=/tmp/elsewhere/imports.jsonl', PublishAndSynchronizeMetricsConstants::ENV_IMPORT_OUTPUT_FILE));
        putenv(sprintf('%s=/data/metrics/run-1/task-runs.jsonl', PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE));

        // Act
        $path = ImportRunCollector::resolveOutputFilePath();

        // Assert
        $this->assertSame('/tmp/elsewhere/imports.jsonl', $path);
    }

    /**
     * @return void
     */
    public function testHandsOutOneSharedInstanceSoSeparateHooksMeet(): void
    {
        // Act
        $first = ImportRunCollector::getInstance();
        $second = ImportRunCollector::getInstance();

        // Assert
        $this->assertSame($first, $second);
    }

    /**
     * @return void
     */
    public function testPublishEmitInsideACollectionAfterTheImporterReturnedGetsTheCollectionImportType(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openCollection('glossary');
        $collector->openImporter(static::IMPORTER_ID, 'glossary-importer');
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Act
        $collector->openPublishFromBuffer(['Entity.spy_glossary_key.create' => [1 => true]]);
        $collector->closePublish(null);
        $collector->closeCollection(null);

        // Assert
        $publishRecord = $this->writer[1];
        $this->assertSame(ImportRunCollector::KIND_PUBLISH_EMIT, $publishRecord['kind']);
        $this->assertNull($publishRecord['importer']);
        $this->assertSame('glossary', $publishRecord['import_type']);
    }

    /**
     * @return void
     */
    public function testPublishEmitImportTypeIsTheInnermostOpenImporterBeforeTheCollection(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openCollection('full');
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->openPublishFromBuffer([]);
        $collector->closePublish(null);

        // Assert
        $this->assertSame(static::IMPORT_TYPE, $this->writer[0]['import_type']);
    }

    /**
     * @return void
     */
    public function testPublishEmitImportTypeIsNullNotEmptyOutsideAnyImporterOrCollection(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);

        // Act
        $collector->openPublishFromBuffer([]);
        $collector->closePublish(null);

        // Assert
        $this->assertArrayHasKey('import_type', $this->writer[0]);
        $this->assertNull($this->writer[0]['import_type']);
    }

    /**
     * @return void
     */
    public function testPublishEmitEventCountsByNameSumToEvents(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);

        // Act
        $collector->openPublishFromBuffer([
            'Entity.spy_product.create' => [1 => true, 2 => true],
            'Entity.spy_stock_product.update' => [5 => true, 6 => true, 7 => true],
        ]);
        $collector->closePublish(null);

        // Assert
        $record = $this->writer[0];
        $this->assertSame(['Entity.spy_product.create' => 2, 'Entity.spy_stock_product.update' => 3], $record['events_by_name']);
        $this->assertSame($record['events'], array_sum($record['events_by_name']));
    }

    /**
     * @return void
     */
    public function testPublishEmitEventCountsByNameAreNullNotEmptyWhenTheBufferIsUnreadable(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);

        // Act
        $collector->openPublishFromBuffer(null);
        $collector->closePublish(null);

        // Assert
        $this->assertArrayHasKey('events_by_name', $this->writer[0]);
        $this->assertNull($this->writer[0]['events_by_name']);
        $this->assertNull($this->writer[0]['events']);
    }

    /**
     * Events already in the buffer at importer start belong to an earlier importer; events drained
     * by a chunk flush while the importer runs still belong to it.
     *
     * @return void
     */
    public function testImporterEventCountsByNameAreTheEventsBufferedWhileItRanAcrossFlushes(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE, ['Entity.spy_product.create' => [1 => true]]);
        $collector->openPublishFromBuffer([
            'Entity.spy_product.create' => [1 => true, 2 => true, 3 => true],
            'Entity.spy_stock_product.update' => [9 => true],
        ]);
        $collector->closePublish(null);

        // Act
        $collector->closeImporter(
            static::IMPORTER_ID,
            $this->createReport(),
            null,
            ['Entity.spy_stock_product.update' => [10 => true, 11 => true]],
        );

        // Assert
        $this->assertSame(
            ['Entity.spy_product.create' => 2, 'Entity.spy_stock_product.update' => 3],
            $this->writer[1]['events_by_name'],
        );
    }

    /**
     * @return void
     */
    public function testImporterEventCountsByNameAreNullNotEmptyWhenTheBufferWasNotRead(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null, []);

        // Assert
        $this->assertArrayHasKey('events_by_name', $this->writer[0]);
        $this->assertNull($this->writer[0]['events_by_name']);
    }

    /**
     * @return void
     */
    public function testImporterEventCountsByNameAreNullWhenAFlushDuringTheImporterWasUnreadable(): void
    {
        // Arrange
        $collector = new ImportRunCollector($this->writer);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE, []);
        $collector->openPublishFromBuffer(null);
        $collector->closePublish(null);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null, []);

        // Assert
        $this->assertNull($this->writer[1]['events_by_name']);
    }

    /**
     * @return void
     */
    public function testImporterCpuFieldsAreTheCpuSpentWhileItRan(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $collector = new ImportRunCollector($this->writer, $cpuTracker);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);
        $cpuTracker->spend(250000, 40000);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertSame(250.0, $this->writer[0]['cpu_user_ms']);
        $this->assertSame(40.0, $this->writer[0]['cpu_sys_ms']);
    }

    /**
     * @return void
     */
    public function testImporterCpuFieldsAreNullNotZeroWhenCpuCannotBeRead(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $cpuTracker->isReadable = false;
        $collector = new ImportRunCollector($this->writer, $cpuTracker);
        $collector->openImporter(static::IMPORTER_ID, static::IMPORT_TYPE);

        // Act
        $collector->closeImporter(static::IMPORTER_ID, $this->createReport(), null);

        // Assert
        $this->assertArrayHasKey('cpu_user_ms', $this->writer[0]);
        $this->assertNull($this->writer[0]['cpu_user_ms']);
        $this->assertNull($this->writer[0]['cpu_sys_ms']);
    }

    /**
     * @return \Generated\Shared\Transfer\DataImporterReportTransfer
     */
    protected function createReport(): DataImporterReportTransfer
    {
        return (new DataImporterReportTransfer())
            ->setImportType(static::IMPORT_TYPE)
            ->setSource('data/import/common/common/product_stock.csv')
            ->setIsReaderCountable(true)
            ->setExpectedImportableDataSetCount(120)
            ->setImportedDataSetCount(118)
            ->setIsSuccess(true)
            ->setImportTime(0.5);
    }

    /**
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface&\ArrayAccess<int, array<string, mixed>>&\Countable
     */
    protected function createInMemoryWriter(): TaskRunMetricsWriterInterface
    {
        return new class extends ArrayObject implements TaskRunMetricsWriterInterface {
            /**
             * @param array<string, mixed> $record
             *
             * @return void
             */
            public function write(array $record): void
            {
                $this->append($record);
            }
        };
    }
}
