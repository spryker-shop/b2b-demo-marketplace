<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Metrics;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\LatencyHistogram;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Metrics
 * @group LatencyHistogramTest
 * Add your own group annotations below this line
 */
class LatencyHistogramTest extends Unit
{
    /**
     * @return void
     */
    public function testEmitsNothingWhenNothingWasRecorded(): void
    {
        // Act
        $histogram = (new LatencyHistogram())->toArray();

        // Assert
        $this->assertSame([], $histogram, 'An idle batch must not add noise to the output.');
    }

    /**
     * @return void
     */
    public function testCountsEveryObservation(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        foreach ([0.004, 0.1, 1.5, 300.0] as $milliseconds) {
            $latencyHistogram->record($milliseconds);
        }

        // Assert
        $this->assertSame(4, $latencyHistogram->toArray()['n']);
    }

    /**
     * @return void
     */
    public function testPlacesAnObservationInTheTightestBucketAboveIt(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(1.0);

        // Assert
        $buckets = $latencyHistogram->toArray()['buckets'];
        $this->assertCount(1, $buckets);
        $bound = (float)substr((string)array_key_first($buckets), strlen('le='));
        $this->assertGreaterThanOrEqual(1.0, $bound, 'A bound is inclusive and must not sit below the value.');
        $this->assertLessThan(1.1, $bound, 'Sixteen steps per octave put the next bound within about 4.4 percent.');
    }

    /**
     * @return void
     */
    public function testPlacesAnObservationBeyondTheLastBoundInTheOverflowBucket(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        // Beyond the largest bound the scale reaches, which is about twelve days.
        $latencyHistogram->record(2.0e9);

        // Assert
        $this->assertArrayHasKey('le=+Inf', $latencyHistogram->toArray()['buckets']);
    }

    /**
     * @return void
     */
    public function testKeepsBucketKeysAsStrings(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(1.0);
        $latencyHistogram->record(512.0);

        // Assert
        foreach (array_keys($latencyHistogram->toArray()['buckets']) as $bucketKey) {
            $this->assertIsString(
                $bucketKey,
                'PHP coerces a numeric string array key to an integer, which the le= prefix prevents.',
            );
        }
    }

    /**
     * @return void
     */
    public function testPreservesMinimumAndMaximumOutsideTheBuckets(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(0.004);
        $latencyHistogram->record(12.5);

        // Assert
        $histogram = $latencyHistogram->toArray();
        $this->assertSame(0.004, $histogram['min'], 'Bucketing loses the extremes, so they are kept separately.');
        $this->assertSame(12.5, $histogram['max']);
    }

    /**
     * This is the property the histogram exists for. Percentiles cannot be combined, so the per-batch
     * p50 and p95 emitted elsewhere can never be aggregated into a run-wide figure. Bucket counts can
     * simply be summed, which is what makes cross-batch and cross-run aggregation possible at all.
     *
     * @return void
     */
    public function testBucketsFromSeparateBatchesMergeIntoTheWholeRunHistogram(): void
    {
        // Arrange
        $firstBatch = new LatencyHistogram();
        $secondBatch = new LatencyHistogram();
        $wholeRun = new LatencyHistogram();

        foreach ([0.05, 3.0, 3.5, 700.0] as $milliseconds) {
            $firstBatch->record($milliseconds);
            $wholeRun->record($milliseconds);
        }

        foreach ([0.05, 9.0, 700.0, 90000.0] as $milliseconds) {
            $secondBatch->record($milliseconds);
            $wholeRun->record($milliseconds);
        }

        // Act
        $merged = [];
        foreach ([$firstBatch->toArray()['buckets'], $secondBatch->toArray()['buckets']] as $buckets) {
            foreach ($buckets as $bucketKey => $count) {
                $merged[$bucketKey] = ($merged[$bucketKey] ?? 0) + $count;
            }
        }

        $expected = $wholeRun->toArray()['buckets'];
        ksort($merged);
        ksort($expected);

        // Assert
        $this->assertSame($expected, $merged);
    }

    /**
     * A whole-number bound used to lose its trailing zeros, labelling the 110 ms bucket `le=11`.
     *
     * @return void
     */
    public function testLabelsAWholeNumberBoundWithItsTrailingZeros(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(109.0);

        // Assert
        $this->assertSame(['le=110' => 1], $latencyHistogram->toArray()['buckets']);
    }

    /**
     * The labels are what every reader sorts and reads percentiles by, so their order has to be the
     * order of the bounds they stand for.
     *
     * @return void
     */
    public function testEmitsBucketsInAscendingBoundOrder(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        foreach ([3000.0, 0.5, 2840.0, 109.0, 11.0, 150000.0] as $milliseconds) {
            $latencyHistogram->record($milliseconds);
        }

        // Assert
        $bounds = array_map(
            static fn (string $bucketKey): float => (float)substr($bucketKey, strlen('le=')),
            array_keys($latencyHistogram->toArray()['buckets']),
        );
        $sortedBounds = $bounds;
        sort($sortedBounds);
        $this->assertSame($sortedBounds, $bounds);
    }

    /**
     * Below about 0.0015 ms two adjacent steps print as the same label. Their counts must add up,
     * not overwrite each other.
     *
     * @return void
     */
    public function testSumsStepsThatShareALabel(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(0.0005);
        $latencyHistogram->record(0.00104);

        // Assert
        $this->assertSame(['le=0.001' => 2], $latencyHistogram->toArray()['buckets']);
    }

    /**
     * @return void
     */
    public function testDeclaresTheLabelFormatSoOlderOutputCanBeTranslated(): void
    {
        // Arrange
        $latencyHistogram = new LatencyHistogram();

        // Act
        $latencyHistogram->record(1.0);

        // Assert
        $this->assertSame(2, $latencyHistogram->toArray()['format']);
    }
}
