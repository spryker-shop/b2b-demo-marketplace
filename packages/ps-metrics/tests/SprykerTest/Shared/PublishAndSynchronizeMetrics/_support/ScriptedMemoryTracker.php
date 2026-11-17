<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics;

use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MemoryTracker;

/**
 * Replaces the engine's memory counters with values a test sets, so peaks and resets are exact.
 * The reset behaves like `memory_reset_peak_usage()`: each peak drops to the current usage.
 */
class ScriptedMemoryTracker extends MemoryTracker
{
    public int $realUsageBytes = 0;

    public int $realPeakBytes = 0;

    public int $allocatedUsageBytes = 0;

    public int $allocatedPeakBytes = 0;

    /**
     * @param int $realBytes
     * @param int $allocatedBytes
     *
     * @return void
     */
    public function allocate(int $realBytes, int $allocatedBytes): void
    {
        $this->realUsageBytes += $realBytes;
        $this->allocatedUsageBytes += $allocatedBytes;
        $this->realPeakBytes = max($this->realPeakBytes, $this->realUsageBytes);
        $this->allocatedPeakBytes = max($this->allocatedPeakBytes, $this->allocatedUsageBytes);
    }

    /**
     * @return int
     */
    public function readRealUsageBytes(): int
    {
        return $this->realUsageBytes;
    }

    /**
     * @return int
     */
    protected function readRealPeakBytes(): int
    {
        return $this->realPeakBytes;
    }

    /**
     * @return int
     */
    protected function readAllocatedUsageBytes(): int
    {
        return $this->allocatedUsageBytes;
    }

    /**
     * @return int
     */
    protected function readAllocatedPeakBytes(): int
    {
        return $this->allocatedPeakBytes;
    }

    /**
     * @return void
     */
    protected function resetEnginePeak(): void
    {
        $this->realPeakBytes = $this->realUsageBytes;
        $this->allocatedPeakBytes = $this->allocatedUsageBytes;
    }
}
