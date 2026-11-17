<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

interface LatencyHistogramInterface
{
    /**
     * Specification:
     * - Records one observation, in milliseconds, into its bucket.
     *
     * @param float $milliseconds
     *
     * @return void
     */
    public function record(float $milliseconds): void;

    /**
     * Specification:
     * - Returns the bucket counts plus count, sum, min and max.
     * - Buckets are a fixed, shared scale, so the arrays from two runs can be summed elementwise.
     *   This is the whole point of emitting a histogram rather than percentiles: percentiles cannot
     *   be merged, so per-batch p95 values can never be combined into a run-wide p95.
     * - Returns an empty array when nothing was recorded, so an idle batch adds no noise.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
