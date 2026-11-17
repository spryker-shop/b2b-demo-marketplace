<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Generated\Shared\Transfer\DataImporterReportTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\JsonLinesTaskRunMetricsWriter;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\LatencyHistogram;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use Throwable;

/**
 * Turns the import hooks' open and close calls into `import-runs.jsonl` records.
 *
 * Every record is written the moment its span closes. `data:import` is a long-lived process, and a
 * shutdown function does not run when a process is killed, so holding records until shutdown would
 * lose exactly the runs that took too long.
 *
 * Process-global for the same reason as the queue-side collectors: hooks have no constructor and no
 * container, so the only way the separate callbacks can meet is a shared instance.
 */
class ImportRunCollector
{
    /**
     * @var string
     */
    public const string KIND_IMPORTER = 'importer';

    /**
     * @var string
     */
    public const string KIND_COLLECTION = 'collection';

    /**
     * @var string
     */
    public const string KIND_PUBLISH_EMIT = 'publish_emit';

    /**
     * @var string
     */
    public const string KIND_ROWS = 'rows';

    /**
     * @var string
     */
    protected const string DEFAULT_FILE_NAME = 'import-runs.jsonl';

    /**
     * @var int
     */
    protected const int BYTES_PER_MEGABYTE = 1048576;

    protected static ?self $instance = null;

    protected TaskRunMetricsWriterInterface $writer;

    protected bool $isRowsEnabled = false;

    protected bool $isMessageCountingAvailable = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $importerFrames = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $collectionFrames = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $publishFrames = [];

    protected int $dataSetDepth = 0;

    protected float|int|null $dataSetStartedAt = null;

    protected CpuTracker $cpuTracker;

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface|null $writer
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker|null $cpuTracker
     */
    final public function __construct(?TaskRunMetricsWriterInterface $writer = null, ?CpuTracker $cpuTracker = null)
    {
        $this->writer = $writer ?? new JsonLinesTaskRunMetricsWriter(static::resolveOutputFilePath());
        $this->cpuTracker = $cpuTracker ?? CpuTracker::getInstance();
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
     * @return string
     */
    public static function resolveOutputFilePath(): string
    {
        $configured = getenv(PublishAndSynchronizeMetricsConstants::ENV_IMPORT_OUTPUT_FILE);

        if ($configured !== false && $configured !== '') {
            return $configured;
        }

        $taskRunFile = getenv(PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE);

        // Written beside the task-run records so one bundle holds the import and the queue view of
        // the same run.
        if ($taskRunFile !== false && $taskRunFile !== '') {
            return dirname($taskRunFile) . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
        }

        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
    }

    /**
     * @param bool $isRowsEnabled
     *
     * @return void
     */
    public function setIsRowsEnabled(bool $isRowsEnabled): void
    {
        $this->isRowsEnabled = $isRowsEnabled;
    }

    /**
     * @param bool $isMessageCountingAvailable
     *
     * @return void
     */
    public function setIsMessageCountingAvailable(bool $isMessageCountingAvailable): void
    {
        $this->isMessageCountingAvailable = $isMessageCountingAvailable;
    }

    /**
     * @param int $importerId
     * @param string|null $importType
     * @param mixed $bufferedEntityEvents The publisher's event buffer at importer start; null when it
     *   could not be read, which makes the importer's `events_by_name` null.
     *
     * @return void
     */
    public function openImporter(int $importerId, ?string $importType, mixed $bufferedEntityEvents = null): void
    {
        // An importer subclass that overrides import() and calls parent::import() fires the hook
        // twice for one run. Only the outer call is recorded, so the run is not counted twice.
        $isNested = false;

        foreach ($this->importerFrames as $importerFrame) {
            if ($importerFrame['id'] === $importerId) {
                $isNested = true;
            }
        }

        $this->importerFrames[] = [
            'id' => $importerId,
            'import_type' => $importType,
            'is_nested' => $isNested,
            'ts' => microtime(true),
            'started_at' => hrtime(true),
            'rows' => 0,
            'histogram' => null,
            'events_at_open' => $this->countBufferedEventsByName($bufferedEntityEvents),
            'events_flushed' => [],
            'is_events_flushed_readable' => true,
            'cpu' => $this->cpuTracker->beginScope(),
        ];
    }

    /**
     * @param int $importerId
     * @param mixed $response
     * @param \Throwable|null $throwable
     * @param mixed $bufferedEntityEvents The publisher's event buffer at importer end; null when it
     *   could not be read.
     *
     * @return void
     */
    public function closeImporter(int $importerId, mixed $response, ?Throwable $throwable, mixed $bufferedEntityEvents = null): void
    {
        $importerFrame = array_pop($this->importerFrames);

        if ($importerFrame === null || $importerFrame['id'] !== $importerId || $importerFrame['is_nested']) {
            return;
        }

        $wallMilliseconds = (hrtime(true) - $importerFrame['started_at']) / 1e6;
        $cpu = $this->cpuTracker->endScope($importerFrame['cpu']);
        $dataImporterReportTransfer = $response instanceof DataImporterReportTransfer ? $response : null;

        $record = $this->buildImporterRecord($importerFrame, $dataImporterReportTransfer, $wallMilliseconds, $throwable)
            + CpuTracker::toRecordFields($cpu)
            + ['events_by_name' => $this->resolveImporterEventsByName($importerFrame, $this->countBufferedEventsByName($bufferedEntityEvents))];

        $this->writer->write($record);

        if ($this->isRowsEnabled) {
            $this->writer->write($this->buildRowsRecord($importerFrame, $dataImporterReportTransfer));
        }
    }

    /**
     * @param string|null $requestedImportType
     *
     * @return void
     */
    public function openCollection(?string $requestedImportType): void
    {
        $this->collectionFrames[] = [
            'import_type' => $requestedImportType,
            'ts' => microtime(true),
            'started_at' => hrtime(true),
        ];
    }

    /**
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    public function closeCollection(?Throwable $throwable): void
    {
        $collectionFrame = array_pop($this->collectionFrames);

        if ($collectionFrame === null) {
            return;
        }

        $record = $this->createRecordHead(static::KIND_COLLECTION, $collectionFrame['ts']) + [
            'import_type' => $collectionFrame['import_type'],
            'ms_wall' => round((hrtime(true) - $collectionFrame['started_at']) / 1e6, 3),
        ];

        if ($throwable !== null) {
            $record['error'] = get_class($throwable);
        }

        $this->writer->write($record);
    }

    /**
     * Opens a publish span from the publisher's buffer as `triggerEvents()` is about to drain it, so
     * `events` and `events_by_name` come from the same read and always agree.
     *
     * @param mixed $bufferedEntityEvents The publisher's buffer: event name to entity map; null when
     *   it could not be read.
     *
     * @return void
     */
    public function openPublishFromBuffer(mixed $bufferedEntityEvents): void
    {
        $eventCountsByName = $this->countBufferedEventsByName($bufferedEntityEvents);

        $this->openPublish($eventCountsByName === null ? null : array_sum($eventCountsByName), $eventCountsByName);
    }

    /**
     * @param int|null $bufferedEventCount Null when the buffer could not be read.
     * @param array<string, int>|null $bufferedEventCountsByName Null when not measured.
     *
     * @return void
     */
    public function openPublish(?int $bufferedEventCount, ?array $bufferedEventCountsByName = null): void
    {
        $this->addFlushedEventsToOpenImporters($bufferedEventCountsByName);

        $this->publishFrames[] = [
            'events' => $bufferedEventCount,
            'events_by_name' => $bufferedEventCountsByName,
            'messages' => 0,
            'importer' => $this->findEnclosingImporterType(),
            'import_type' => $this->findInnermostImportType(),
            'ts' => microtime(true),
            'started_at' => hrtime(true),
        ];
    }

    /**
     * @param int $messageCount
     *
     * @return void
     */
    public function countEnqueuedMessages(int $messageCount): void
    {
        // Messages sent outside a publish span belong to something else, such as a queue worker
        // re-publishing, and are not this collector's to count.
        if (!$this->publishFrames) {
            return;
        }

        $this->publishFrames[array_key_last($this->publishFrames)]['messages'] += $messageCount;
    }

    /**
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    public function closePublish(?Throwable $throwable): void
    {
        $publishFrame = array_pop($this->publishFrames);

        if ($publishFrame === null) {
            return;
        }

        $record = $this->createRecordHead(static::KIND_PUBLISH_EMIT, $publishFrame['ts']) + [
            'ms' => round((hrtime(true) - $publishFrame['started_at']) / 1e6, 3),
            'events' => $publishFrame['events'],
            // Null, not zero, when nothing could count them: zero would claim that nothing was sent.
            'messages' => $this->isMessageCountingAvailable ? $publishFrame['messages'] : null,
            'importer' => $publishFrame['importer'],
            'events_by_name' => $publishFrame['events_by_name'],
            'import_type' => $publishFrame['import_type'],
        ];

        if ($throwable !== null) {
            $record['error'] = get_class($throwable);
        }

        $this->writer->write($record);
    }

    /**
     * @return void
     */
    public function openDataSet(): void
    {
        // A transaction-aware broker's execute() calls parent::execute(), which fires the same hook
        // again. Only the outermost call is one data set.
        if ($this->dataSetDepth === 0) {
            $this->dataSetStartedAt = hrtime(true);
        }

        $this->dataSetDepth++;
    }

    /**
     * @return void
     */
    public function closeDataSet(): void
    {
        if ($this->dataSetDepth === 0) {
            return;
        }

        $this->dataSetDepth--;

        if ($this->dataSetDepth > 0 || $this->dataSetStartedAt === null) {
            return;
        }

        $milliseconds = (hrtime(true) - $this->dataSetStartedAt) / 1e6;
        $this->dataSetStartedAt = null;

        $this->recordDataSet($milliseconds);
    }

    /**
     * @param float $milliseconds
     *
     * @return void
     */
    public function recordDataSet(float $milliseconds): void
    {
        if (!$this->isRowsEnabled) {
            return;
        }

        // Attributed to the innermost importer that is recorded, so a nested parent::import() call
        // does not take the rows with it when its frame is discarded.
        for ($index = count($this->importerFrames) - 1; $index >= 0; $index--) {
            if ($this->importerFrames[$index]['is_nested']) {
                continue;
            }

            if ($this->importerFrames[$index]['histogram'] === null) {
                $this->importerFrames[$index]['histogram'] = new LatencyHistogram();
            }

            $this->importerFrames[$index]['rows']++;
            $this->importerFrames[$index]['histogram']->record($milliseconds);

            return;
        }
    }

    /**
     * @param mixed $bufferedEntityEvents The publisher's buffer: event name to entity map.
     *
     * @return int|null Null when the buffer does not have the expected shape.
     */
    public function countBufferedEvents(mixed $bufferedEntityEvents): ?int
    {
        $eventCountsByName = $this->countBufferedEventsByName($bufferedEntityEvents);

        return $eventCountsByName === null ? null : array_sum($eventCountsByName);
    }

    /**
     * @param mixed $bufferedEntityEvents The publisher's buffer: event name to entity map.
     *
     * @return array<string, int>|null Entity events per event name, or null when the buffer does not
     *   have the expected shape.
     */
    public function countBufferedEventsByName(mixed $bufferedEntityEvents): ?array
    {
        if (!is_array($bufferedEntityEvents)) {
            return null;
        }

        $eventCountsByName = [];

        foreach ($bufferedEntityEvents as $eventName => $entityEvents) {
            $eventCountsByName[(string)$eventName] = is_array($entityEvents) ? count($entityEvents) : 1;
        }

        return $eventCountsByName;
    }

    /**
     * @param array<string, mixed> $importerFrame
     * @param \Generated\Shared\Transfer\DataImporterReportTransfer|null $dataImporterReportTransfer
     * @param float $wallMilliseconds
     * @param \Throwable|null $throwable
     *
     * @return array<string, mixed>
     */
    protected function buildImporterRecord(
        array $importerFrame,
        ?DataImporterReportTransfer $dataImporterReportTransfer,
        float $wallMilliseconds,
        ?Throwable $throwable
    ): array {
        $importTime = $dataImporterReportTransfer?->getImportTime();
        $isReaderCountable = $dataImporterReportTransfer?->getIsReaderCountable();
        $isSuccess = $dataImporterReportTransfer?->getIsSuccess();

        $record = $this->createRecordHead(static::KIND_IMPORTER, $importerFrame['ts']) + [
            'import_type' => $dataImporterReportTransfer?->getImportType() ?? $importerFrame['import_type'],
            'source' => $dataImporterReportTransfer?->getSource(),
            // A reader that cannot be counted has no expected total; zero would read as an empty file.
            'expected' => $isReaderCountable ? $dataImporterReportTransfer->getExpectedImportableDataSetCount() : null,
            'imported' => $dataImporterReportTransfer?->getImportedDataSetCount(),
            // A run that threw has no report, and it did not succeed.
            'is_success' => $throwable !== null ? false : $isSuccess,
            // Not rounded: the console prints this to 0.01 s, which is the precision being replaced.
            'ms_native' => $importTime !== null ? (float)$importTime * 1000 : null,
            'ms_wall' => round($wallMilliseconds, 3),
            'peak_mb' => round(memory_get_peak_usage(true) / static::BYTES_PER_MEGABYTE, 2),
        ];

        if ($throwable !== null) {
            $record['error'] = get_class($throwable);
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $importerFrame
     * @param \Generated\Shared\Transfer\DataImporterReportTransfer|null $dataImporterReportTransfer
     *
     * @return array<string, mixed>
     */
    protected function buildRowsRecord(array $importerFrame, ?DataImporterReportTransfer $dataImporterReportTransfer): array
    {
        $histogram = $importerFrame['histogram'];

        return $this->createRecordHead(static::KIND_ROWS, $importerFrame['ts']) + [
            'import_type' => $dataImporterReportTransfer?->getImportType() ?? $importerFrame['import_type'],
            'n' => $importerFrame['rows'],
            'hist' => $histogram instanceof LatencyHistogram ? $histogram->toArray() : null,
        ];
    }

    /**
     * @param string $kind
     * @param float $startedAt
     *
     * @return array<string, mixed>
     */
    protected function createRecordHead(string $kind, float $startedAt): array
    {
        return [
            'kind' => $kind,
            'ts' => round($startedAt, 6),
            'pid' => getmypid(),
        ];
    }

    /**
     * @return string|null
     */
    protected function findEnclosingImporterType(): ?string
    {
        if (!$this->importerFrames) {
            return null;
        }

        return $this->importerFrames[array_key_last($this->importerFrames)]['import_type'];
    }

    /**
     * The innermost open importer, else the innermost open collection. In a `--config` run most
     * flushes run in the collection's after-import hooks, after the importer returned, so only the
     * collection still knows the type there.
     *
     * @return string|null
     */
    protected function findInnermostImportType(): ?string
    {
        foreach ([$this->importerFrames, $this->collectionFrames] as $frames) {
            $importType = $this->findInnermostFrameImportType($frames);

            if ($importType !== null) {
                return $importType;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $frames
     *
     * @return string|null
     */
    protected function findInnermostFrameImportType(array $frames): ?string
    {
        foreach (array_reverse($frames) as $frame) {
            if (is_string($frame['import_type']) && $frame['import_type'] !== '') {
                return $frame['import_type'];
            }
        }

        return null;
    }

    /**
     * A flush drains the whole buffer, so every importer still open loses those events from the
     * buffer it will read at close; keeping them here lets the close compute what it buffered.
     *
     * @param array<string, int>|null $flushedEventCountsByName
     *
     * @return void
     */
    protected function addFlushedEventsToOpenImporters(?array $flushedEventCountsByName): void
    {
        foreach ($this->importerFrames as $index => $importerFrame) {
            if ($flushedEventCountsByName === null) {
                $this->importerFrames[$index]['is_events_flushed_readable'] = false;

                continue;
            }

            foreach ($flushedEventCountsByName as $eventName => $eventCount) {
                $this->importerFrames[$index]['events_flushed'][$eventName] = ($importerFrame['events_flushed'][$eventName] ?? 0) + $eventCount;
            }
        }
    }

    /**
     * Events buffered while the importer ran: buffer at close, plus what flushes drained while it was
     * open, minus what was already in the buffer at open. The buffer only grows by `addEvent()` and
     * only shrinks by `triggerEvents()`, which is hooked, so the sum is exact without a per-row hook.
     *
     * @param array<string, mixed> $importerFrame
     * @param array<string, int>|null $eventCountsAtClose
     *
     * @return array<string, int>|null Null when any of the reads failed or the counts do not add up.
     */
    protected function resolveImporterEventsByName(array $importerFrame, ?array $eventCountsAtClose): ?array
    {
        $eventCountsAtOpen = $importerFrame['events_at_open'];

        if ($eventCountsAtOpen === null || $eventCountsAtClose === null || !$importerFrame['is_events_flushed_readable']) {
            return null;
        }

        $eventCountsByName = [];
        $eventNames = array_keys($eventCountsAtClose + $importerFrame['events_flushed'] + $eventCountsAtOpen);

        foreach ($eventNames as $eventName) {
            $eventCount = ($eventCountsAtClose[$eventName] ?? 0) + ($importerFrame['events_flushed'][$eventName] ?? 0) - ($eventCountsAtOpen[$eventName] ?? 0);

            // Negative means something other than triggerEvents() emptied the buffer: not measurable.
            if ($eventCount < 0) {
                return null;
            }

            if ($eventCount > 0) {
                $eventCountsByName[(string)$eventName] = $eventCount;
            }
        }

        ksort($eventCountsByName);

        return $eventCountsByName;
    }
}
