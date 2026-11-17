<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface;
use Throwable;

/**
 * Builds and writes one `direct_sync_flush` record (contract C6) per flush of the direct-sync buffer.
 *
 * The flush runs on console terminate, after the task run or the import has finished, so it owns a
 * breakdown span of its own: storage, search, database and broker calls made while it runs are
 * attributed to this record rather than dropped. The record is written as soon as the flush returns,
 * because a shutdown function would run after it at best and not at all when the process is killed.
 *
 * Memory (contract C7), all real memory: `peak_mb` is the process peak at flush end from the module's
 * running maximum, `mem_start_mb` the usage at flush start, and `mem_peak_incr_mb` the peak during
 * the flush itself minus that start. The increment is measured as a scope rather than as
 * `peak_mb - mem_start_mb`: the process peak usually belongs to the task run or the import that ran
 * before the flush, and subtracting from it would charge that peak to the flush.
 */
class SyncFlushCollector
{
    /**
     * @var string
     */
    public const string KIND = 'direct_sync_flush';

    /**
     * @var string
     */
    public const string CONTEXT_QUEUE = 'queue';

    /**
     * @var string
     */
    public const string CONTEXT_IMPORT = 'import';

    /**
     * @var string
     */
    public const string CONTEXT_OTHER = 'other';

    /**
     * @var string
     */
    protected const string IMPORT_COMMAND_PREFIX = 'data:import';

    protected static ?self $instance = null;

    protected ?string $queueName = null;

    protected bool $hasImporterRun = false;

    protected ?string $commandName = null;

    /**
     * @var float|int|null
     */
    protected float|int|null $startedAt = null;

    protected ?float $startedAtTimestamp = null;

    protected ?int $bufferedMessageCount = null;

    protected ?int $breakdownToken = null;

    /**
     * @var array<string, int>|null
     */
    protected ?array $memoryScope = null;

    protected BreakdownCollector $breakdownCollector;

    protected MemoryTracker $memoryTracker;

    protected CpuTracker $cpuTracker;

    /**
     * @var array<string, int>|null
     */
    protected ?array $cpuScope = null;

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface $writer
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector|null $breakdownCollector
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MemoryTracker|null $memoryTracker
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker|null $cpuTracker
     */
    final public function __construct(
        protected TaskRunMetricsWriterInterface $writer,
        ?BreakdownCollector $breakdownCollector = null,
        ?MemoryTracker $memoryTracker = null,
        ?CpuTracker $cpuTracker = null
    ) {
        $this->breakdownCollector = $breakdownCollector ?? BreakdownCollector::getInstance();
        $this->memoryTracker = $memoryTracker ?? MemoryTracker::getInstance();
        $this->cpuTracker = $cpuTracker ?? CpuTracker::getInstance();
    }

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\TaskRunMetricsWriterInterface $writer
     *
     * @return self
     */
    public static function getInstance(TaskRunMetricsWriterInterface $writer): self
    {
        if (static::$instance === null) {
            static::$instance = new self($writer);
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
     * @return void
     */
    public function markImporterRun(): void
    {
        $this->hasImporterRun = true;
    }

    /**
     * @param string|null $commandName
     *
     * @return void
     */
    public function setCommandName(?string $commandName): void
    {
        $this->commandName = $commandName;
    }

    /**
     * @param int|null $bufferedMessageCount Null when the buffer could not be read.
     *
     * @return void
     */
    public function openFlush(?int $bufferedMessageCount): void
    {
        $this->bufferedMessageCount = $bufferedMessageCount;
        $this->memoryScope = $this->memoryTracker->beginScope();
        $this->cpuScope = $this->cpuTracker->beginScope();
        $this->startedAtTimestamp = microtime(true);
        $this->breakdownToken = $this->breakdownCollector->openSpan();
        $this->startedAt = hrtime(true);
    }

    /**
     * Writes the record of the flush that is returning, or nothing when no flush is open or the buffer
     * was known to be empty: an empty flush does no I/O, and every console process ends with one, so
     * recording it would add a line per idle worker process and say nothing.
     *
     * @param \Throwable|null $throwable
     *
     * @return array<string, mixed>|null The record written, or null when none was.
     */
    public function closeFlush(?Throwable $throwable): ?array
    {
        if ($this->startedAt === null) {
            return null;
        }

        $milliseconds = (hrtime(true) - $this->startedAt) / 1e6;
        $breakdown = $this->breakdownCollector->closeSpan($this->breakdownToken);
        $memoryStartBytes = $this->memoryScope[MemoryTracker::SCOPE_REAL] ?? null;
        $memoryIncrement = $this->memoryTracker->endScope($this->memoryScope);
        $cpu = $this->cpuTracker->endScope($this->cpuScope);
        $this->cpuScope = null;
        $this->startedAt = null;
        $this->breakdownToken = null;
        $this->memoryScope = null;

        if ($this->bufferedMessageCount === 0 && $throwable === null) {
            return null;
        }

        $record = [
            'kind' => static::KIND,
            'ts' => round((float)$this->startedAtTimestamp, 6),
            'pid' => getmypid(),
            'context' => $this->resolveContext(),
            'queue' => $this->queueName,
            'command' => $this->commandName,
            'ms' => $milliseconds,
            'messages' => $this->bufferedMessageCount,
            'breakdown' => $breakdown,
            'error' => $throwable !== null ? get_class($throwable) : null,
            'peak_mb' => MemoryTracker::toMegabytes($this->memoryTracker->observe()),
            'mem_start_mb' => MemoryTracker::toMegabytes($memoryStartBytes),
            'mem_peak_incr_mb' => MemoryTracker::toMegabytes($memoryIncrement[MemoryTracker::SCOPE_REAL] ?? null),
        ] + CpuTracker::toRecordFields($cpu);

        $this->writer->write($record);

        return $record;
    }

    /**
     * @return string
     */
    public function resolveContext(): string
    {
        if ($this->queueName !== null) {
            return static::CONTEXT_QUEUE;
        }

        if ($this->hasImporterRun || str_starts_with((string)$this->commandName, static::IMPORT_COMMAND_PREFIX)) {
            return static::CONTEXT_IMPORT;
        }

        return static::CONTEXT_OTHER;
    }
}
