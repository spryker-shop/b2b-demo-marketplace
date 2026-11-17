<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

class LatencyHistogram implements LatencyHistogramInterface
{
    /**
     * Smallest bound, in milliseconds. Anything faster lands in the first bucket.
     *
     * @var float
     */
    protected const float BASE_MS = 0.001;

    /**
     * Bounds per doubling. Sixteen puts each bound about 4.4 percent above the last.
     *
     * The scale is FIXED rather than fitted to the observed range, because the one property that
     * makes these histograms worth emitting is that bucket counts from different batches, queues
     * and runs can be summed, and two histograms fitted to their own ranges cannot be.
     *
     * Fine bounds cost nothing on clustered data: only populated buckets are emitted, so a batch
     * whose values all sit near 150 ms writes a handful of keys no matter how many bounds exist.
     * Powers of two were the mistake here, putting every such message into one 128-to-256 bucket,
     * which is not a distribution.
     *
     * @var int
     */
    protected const int STEPS_PER_OCTAVE = 16;

    /**
     * Guards against a pathological value producing an unbounded key.
     *
     * @var int
     */
    protected const int MAX_STEPS = 640;

    /**
     * @var string
     */
    protected const string OVERFLOW_BUCKET = 'le=+Inf';

    /**
     * Prefix borrowed from the Prometheus convention, where a bucket key means "less than or equal
     * to this bound". It also keeps the keys non-numeric: PHP coerces a numeric string array key
     * such as '1' to the integer 1, which would leave the bucket map with mixed key types.
     *
     * @var string
     */
    protected const string BUCKET_PREFIX = 'le=';

    /**
     * Version of the bucket labels. Format 1 stripped trailing zeros from whole-number bounds, so a
     * 2840 ms bound was labelled `le=284`. Readers of format 1 data must translate the labels; the
     * mapping from each format 1 label to its true bound is one to one.
     *
     * @var int
     */
    protected const int LABEL_FORMAT = 2;

    /**
     * Counts keyed by step index. The `le=` key strings exist only in the output: one formatted
     * string per bucket per histogram measured 64 KB for a fully populated histogram, held for the
     * life of a collector that keeps one histogram per listener and event.
     *
     * @var array<int, int>
     */
    protected array $buckets = [];

    protected int $overflowCount = 0;

    protected int $count = 0;

    protected float $sum = 0.0;

    protected ?float $min = null;

    protected ?float $max = null;

    /**
     * {@inheritDoc}
     */
    public function record(float $milliseconds): void
    {
        $this->count++;
        $this->sum += $milliseconds;
        $this->min = $this->min === null ? $milliseconds : min($this->min, $milliseconds);
        $this->max = $this->max === null ? $milliseconds : max($this->max, $milliseconds);

        $step = $this->resolveStep($milliseconds);

        if ($step === null) {
            $this->overflowCount++;

            return;
        }

        $this->buckets[$step] = ($this->buckets[$step] ?? 0) + 1;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->count === 0) {
            return [];
        }

        $steps = $this->buckets;
        ksort($steps);

        $buckets = [];
        foreach ($steps as $step => $count) {
            $key = $this->formatBound(static::BASE_MS * (2 ** ($step / static::STEPS_PER_OCTAVE)));
            // Summed, not assigned: below about 0.0015 ms two adjacent steps print as the same key.
            $buckets[$key] = ($buckets[$key] ?? 0) + $count;
        }

        if ($this->overflowCount > 0) {
            $buckets[static::OVERFLOW_BUCKET] = $this->overflowCount;
        }

        return [
            'n' => $this->count,
            'sum' => round($this->sum, 4),
            'min' => round((float)$this->min, 4),
            'max' => round((float)$this->max, 4),
            'format' => static::LABEL_FORMAT,
            'buckets' => $buckets,
        ];
    }

    /**
     * @param float $milliseconds
     *
     * @return int|null Null when the value lies beyond the largest bound.
     */
    protected function resolveStep(float $milliseconds): ?int
    {
        if ($milliseconds <= static::BASE_MS) {
            return 0;
        }

        // Computed rather than scanned: the bound index is a logarithm, so the bucket is found in
        // constant time however fine the scale is.
        $step = (int)ceil(log($milliseconds / static::BASE_MS, 2) * static::STEPS_PER_OCTAVE);

        if ($step > static::MAX_STEPS) {
            return null;
        }

        return $step;
    }

    /**
     * @param float $bound
     *
     * @return string
     */
    protected function formatBound(float $bound): string
    {
        $decimals = $bound < 1 ? 4 : ($bound < 100 ? 2 : 0);
        $label = number_format($bound, $decimals, '.', '');

        if ($decimals === 0) {
            return static::BUCKET_PREFIX . $label;
        }

        return static::BUCKET_PREFIX . rtrim(rtrim($label, '0'), '.');
    }
}
