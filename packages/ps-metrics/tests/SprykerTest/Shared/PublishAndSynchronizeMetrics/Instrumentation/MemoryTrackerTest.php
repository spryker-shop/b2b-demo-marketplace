<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MemoryTracker;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\TaskRunCollector;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\ScriptedMemoryTracker;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group MemoryTrackerTest
 * Add your own group annotations below this line
 */
class MemoryTrackerTest extends Unit
{
    /**
     * @var int
     */
    protected const int MEGABYTE = 1048576;

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        MemoryTracker::reset();
        TaskRunCollector::reset();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testTaskRunPeakNeverGoesBelowAnEarlierObservedPeakAfterAListenerReset(): void
    {
        // Arrange
        $memoryTracker = new ScriptedMemoryTracker();
        $memoryTracker->allocate(100 * static::MEGABYTE, 0);
        $memoryTracker->allocate(-80 * static::MEGABYTE, 0);
        $taskRunCollector = new TaskRunCollector();
        $taskRunCollector->beforeProcessing([]);

        // Act
        $memoryTracker->endScope($memoryTracker->beginScope());
        $taskRunCollector->setMemoryPeak($memoryTracker->observe());
        $record = $taskRunCollector->buildRecord(1.0);

        // Assert
        $this->assertSame(20 * static::MEGABYTE, $memoryTracker->realPeakBytes);
        $this->assertSame(100.0, $record['peak_mb']);
    }

    /**
     * Runs against the engine itself: the reset really lowers `memory_get_peak_usage(true)`, and the
     * tracker still reports the peak observed before it.
     *
     * @return void
     */
    public function testTaskRunPeakNeverGoesBelowAnEarlierObservedPeakAfterAnEngineReset(): void
    {
        // Arrange
        $memoryTracker = new MemoryTracker();
        $ballast = str_repeat('x', 16 * static::MEGABYTE);
        $observedPeakBytes = $memoryTracker->observe();
        unset($ballast);

        // Act
        $memoryTracker->endScope($memoryTracker->beginScope());

        // Assert
        $this->assertGreaterThanOrEqual($observedPeakBytes, $memoryTracker->observe());
        $this->assertGreaterThanOrEqual(memory_get_peak_usage(true), $memoryTracker->observe());
    }

    /**
     * @return void
     */
    public function testScopeIncrementIsThePeakDuringTheScopeMinusTheUsageAtScopeStart(): void
    {
        // Arrange
        $memoryTracker = new ScriptedMemoryTracker();
        $memoryTracker->allocate(10 * static::MEGABYTE, 5000);
        $scope = $memoryTracker->beginScope();
        $memoryTracker->allocate(2 * static::MEGABYTE, 3000);
        $memoryTracker->allocate(-2 * static::MEGABYTE, -3000);

        // Act
        $increment = $memoryTracker->endScope($scope);

        // Assert
        $this->assertSame(
            [MemoryTracker::SCOPE_REAL => 2 * static::MEGABYTE, MemoryTracker::SCOPE_ALLOCATED => 3000],
            $increment,
        );
    }

    /**
     * @return void
     */
    public function testMissingScopeMeasurementIsNull(): void
    {
        // Act
        $increment = (new ScriptedMemoryTracker())->endScope(null);

        // Assert
        $this->assertNull($increment);
    }

    /**
     * @return void
     */
    public function testMissingMeasurementConvertsToNullMegabytesNotZero(): void
    {
        // Assert
        $this->assertNull(MemoryTracker::toMegabytes(null));
        $this->assertSame(0.0, MemoryTracker::toMegabytes(0));
    }
}
