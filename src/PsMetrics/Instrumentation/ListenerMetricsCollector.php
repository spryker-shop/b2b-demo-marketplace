<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\LatencyHistogram;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\LatencyHistogramInterface;

/**
 * Accumulates per-listener timings for one queue task process and writes them once, at shutdown.
 *
 * Process-global by necessity: an engine hook has no constructor and no container to resolve a
 * collaborator from, so the hook callback and the shutdown writer can only meet through a shared
 * instance. This is the reason the class lives in Shared and not in a Business layer, where the
 * statelessness rule applies.
 */
class ListenerMetricsCollector
{
    /**
     * Listener memory is the allocated kind (`memory_get_usage(false)`), not real memory: real memory
     * moves in 2 MB chunks, so most listener calls read 0 and a few read 2048 KB, which says where the
     * allocator crossed a chunk boundary and nothing about the listener. See `MemoryTracker`.
     *
     * @var string
     */
    public const string MEMORY_KIND = MemoryTracker::SCOPE_ALLOCATED;

    /**
     * @var float
     */
    protected const float BYTES_PER_KILOBYTE = 1024.0;

    protected static ?self $instance = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $groups = [];

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
     * Exists so a test can assert against a clean collector; nothing in the runtime path calls it.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * @param string $listenerClassName
     * @param array<string, int> $itemCountsByEventName
     * @param float $milliseconds
     * @param array<string, float|int|null>|null $breakdown
     * @param int|null $memoryPeakIncrementBytes Peak during the call minus usage at call start, of the
     *   `MEMORY_KIND` memory; null when the call was not measured.
     * @param array<string, float>|null $cpu The value `CpuTracker::endScope()` returned for the call.
     *
     * @return void
     */
    public function record(
        string $listenerClassName,
        array $itemCountsByEventName,
        float $milliseconds,
        ?array $breakdown = null,
        ?int $memoryPeakIncrementBytes = null,
        ?array $cpu = null
    ): void {
        foreach ($itemCountsByEventName as $eventName => $itemCount) {
            $key = $this->getGroupKey($listenerClassName, $eventName);

            $this->groups[$key]['calls']++;
            $this->groups[$key]['messages'] += $itemCount;
            $this->groups[$key]['ms'] += $milliseconds;
            // Each event group gets the whole call, the same way `ms` does.
            $this->groups[$key]['breakdown'] = BreakdownCollector::merge($this->groups[$key]['breakdown'], $breakdown);
            $this->groups[$key]['cpu'] = CpuTracker::merge($this->groups[$key]['cpu'], $cpu);

            // The call is timed as a whole, so a per-message figure is only meaningful as the share
            // of this call. Recording it per message would invent observations that were not made.
            if ($itemCount > 0) {
                $this->groups[$key]['histogram']->record($milliseconds / $itemCount);
            }

            $this->recordMemory($key, $itemCount, $memoryPeakIncrementBytes);
        }
    }

    /**
     * A bulk call that threw; the consumer then retries the same items one call each.
     *
     * @param string $listenerClassName
     * @param string $eventName
     *
     * @return void
     */
    public function recordBulkCallFailure(string $listenerClassName, string $eventName): void
    {
        $this->groups[$this->getGroupKey($listenerClassName, $eventName)]['bulk_call_errors']++;
    }

    /**
     * A single message the listener failed on, which the consumer hands to its retry or error path.
     *
     * @param string $listenerClassName
     * @param string $eventName
     *
     * @return void
     */
    public function recordMessageFailure(string $listenerClassName, string $eventName): void
    {
        $this->groups[$this->getGroupKey($listenerClassName, $eventName)]['failed_messages']++;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        $records = [];

        foreach ($this->groups as $group) {
            $messages = $group['messages'];
            $record = [
                'listener' => $group['listener'],
                'event' => $group['event'],
                'calls' => $group['calls'],
                'messages' => $messages,
                'ms' => round($group['ms'], 3),
                'ms_per_msg' => $messages > 0 ? round($group['ms'] / $messages, 4) : null,
                'ms_per_msg_hist' => $group['histogram']->toArray(),
                'errors' => $group['bulk_call_errors'] + $group['failed_messages'],
                'failed_messages' => $group['failed_messages'],
                'breakdown' => $group['breakdown'],
                'mem_peak_incr_mb' => MemoryTracker::toMegabytes($group['mem_peak_incr_bytes'], 4),
                'mem_peak_incr_hist' => $group['mem_histogram']->toArray() ?: null,
            ] + CpuTracker::toRecordFields($group['cpu']);

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Creates the group on first use, so a listener that only ever failed still gets a record.
     *
     * @param string $listenerClassName
     * @param string $eventName
     *
     * @return string
     */
    protected function getGroupKey(string $listenerClassName, string $eventName): string
    {
        $key = sprintf('%s|%s', $listenerClassName, $eventName);

        if (isset($this->groups[$key])) {
            return $key;
        }

        $this->groups[$key] = [
            'listener' => $this->shortenClassName($listenerClassName),
            'event' => $eventName,
            'calls' => 0,
            'messages' => 0,
            'ms' => 0.0,
            'histogram' => $this->createHistogram(),
            'bulk_call_errors' => 0,
            'failed_messages' => 0,
            'breakdown' => null,
            'mem_peak_incr_bytes' => null,
            'mem_histogram' => $this->createHistogram(),
            'cpu' => null,
        ];

        return $key;
    }

    /**
     * Each event group gets the whole call, the same way `ms` does. The maximum stays null until a
     * call was measured, so a group that only ever failed, or an engine without peak reset, reports
     * null rather than 0.
     *
     * @param string $key
     * @param int $itemCount
     * @param int|null $memoryPeakIncrementBytes
     *
     * @return void
     */
    protected function recordMemory(string $key, int $itemCount, ?int $memoryPeakIncrementBytes): void
    {
        if ($memoryPeakIncrementBytes === null) {
            return;
        }

        $this->groups[$key]['mem_peak_incr_bytes'] = max($this->groups[$key]['mem_peak_incr_bytes'] ?? 0, $memoryPeakIncrementBytes);

        if ($itemCount > 0) {
            $this->groups[$key]['mem_histogram']->record($memoryPeakIncrementBytes / static::BYTES_PER_KILOBYTE / $itemCount);
        }
    }

    /**
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\LatencyHistogramInterface
     */
    protected function createHistogram(): LatencyHistogramInterface
    {
        return new LatencyHistogram();
    }

    /**
     * @param string $listenerClassName
     *
     * @return string
     */
    protected function shortenClassName(string $listenerClassName): string
    {
        $position = strrpos($listenerClassName, '\\');

        return $position === false ? $listenerClassName : substr($listenerClassName, $position + 1);
    }
}
