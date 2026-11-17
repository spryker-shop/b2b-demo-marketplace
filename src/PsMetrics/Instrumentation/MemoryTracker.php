<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

/**
 * Measures memory for the records of contract C7: the process peak, and the peak increment of a
 * scope such as one listener call or one direct-sync flush.
 *
 * A scope peak needs `memory_reset_peak_usage()` at scope start, and that reset lowers the
 * process-wide peak the engine reports. This class therefore keeps its own running maximum of the
 * real process peak and folds the engine peak into it before every reset and at every measurement
 * point. A process peak read from here never goes below a peak observed earlier, whatever scopes
 * were measured in between. Anything that reads the process peak in a process with scopes MUST read
 * it from here.
 *
 * Two kinds of memory are reported per scope:
 * - real: `memory_get_usage(true)`, the memory the engine has mapped from the system. It moves in
 *   2 MB chunks, so a scope that fits in chunks already mapped reads 0 and a scope that crosses a
 *   chunk boundary reads 2048 KB. Right for the process peak and for large scopes such as a flush.
 * - allocated: `memory_get_usage(false)`, the memory handed out to PHP values. It moves per
 *   allocation, so it gives the cost of a small scope. A probe of 1 to 1 000 messages of 500 to
 *   2 000 bytes read 0 KB real in 23 of 30 calls and 2048 KB or 4096 KB in the rest, while the
 *   allocated increment was stable to 0.1 KB across repeats (1.95 KB per 500-byte message).
 */
class MemoryTracker
{
    /**
     * @var int
     */
    public const int BYTES_PER_MEGABYTE = 1048576;

    /**
     * @var string
     */
    public const string SCOPE_REAL = 'real';

    /**
     * @var string
     */
    public const string SCOPE_ALLOCATED = 'allocated';

    protected static ?self $instance = null;

    protected int $maxRealPeakBytes = 0;

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
     * Exists so a test can assert against a clean tracker; nothing in the runtime path calls it.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * @param int|null $bytes
     * @param int $precision
     *
     * @return float|null Null stays null: a missing measurement is never reported as 0.
     */
    public static function toMegabytes(?int $bytes, int $precision = 2): ?float
    {
        return $bytes === null ? null : round($bytes / static::BYTES_PER_MEGABYTE, $precision);
    }

    /**
     * Folds the engine's real peak into the running maximum and returns the maximum.
     *
     * @return int The real process peak in bytes.
     */
    public function observe(): int
    {
        $this->maxRealPeakBytes = max($this->maxRealPeakBytes, $this->readRealPeakBytes());

        return $this->maxRealPeakBytes;
    }

    /**
     * @return int
     */
    public function readRealUsageBytes(): int
    {
        return memory_get_usage(true);
    }

    /**
     * @return bool False on an engine without `memory_reset_peak_usage()` (PHP before 8.2).
     */
    public function canMeasureScopes(): bool
    {
        return function_exists('memory_reset_peak_usage');
    }

    /**
     * Starts a scope: saves the process peak, resets the engine peak, and returns the usage at start.
     *
     * @return array<string, int>|null Usage at start keyed by `SCOPE_REAL` and `SCOPE_ALLOCATED`, or
     *   null when scopes cannot be measured. The value goes back into `endScope()`.
     */
    public function beginScope(): ?array
    {
        if (!$this->canMeasureScopes()) {
            return null;
        }

        // Observed before the reset: the reset is the one event that loses the process peak.
        $this->observe();
        $this->resetEnginePeak();

        return [
            static::SCOPE_REAL => $this->readRealUsageBytes(),
            static::SCOPE_ALLOCATED => $this->readAllocatedUsageBytes(),
        ];
    }

    /**
     * @param array<string, int>|null $scope The value `beginScope()` returned.
     *
     * @return array<string, int>|null Peak during the scope minus usage at scope start, in bytes,
     *   keyed by `SCOPE_REAL` and `SCOPE_ALLOCATED`, or null when the scope was not measured.
     */
    public function endScope(?array $scope): ?array
    {
        if ($scope === null) {
            return null;
        }

        $realPeakBytes = $this->readRealPeakBytes();
        $allocatedPeakBytes = $this->readAllocatedPeakBytes();
        $this->observe();

        return [
            static::SCOPE_REAL => max(0, $realPeakBytes - $scope[static::SCOPE_REAL]),
            static::SCOPE_ALLOCATED => max(0, $allocatedPeakBytes - $scope[static::SCOPE_ALLOCATED]),
        ];
    }

    /**
     * @return int
     */
    protected function readRealPeakBytes(): int
    {
        return memory_get_peak_usage(true);
    }

    /**
     * @return int
     */
    protected function readAllocatedUsageBytes(): int
    {
        return memory_get_usage(false);
    }

    /**
     * @return int
     */
    protected function readAllocatedPeakBytes(): int
    {
        return memory_get_peak_usage(false);
    }

    /**
     * @return void
     */
    protected function resetEnginePeak(): void
    {
        memory_reset_peak_usage();
    }
}
