<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\ScriptedCpuTracker;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group CpuTrackerTest
 * Add your own group annotations below this line
 */
class CpuTrackerTest extends Unit
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        CpuTracker::reset();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testScopeCpuIsTheUsageAtScopeEndMinusTheUsageAtScopeStartInMilliseconds(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $cpuTracker->spend(5000000, 1000000);
        $scope = $cpuTracker->beginScope();
        $cpuTracker->spend(12345, 678);

        // Act
        $cpu = $cpuTracker->endScope($scope);

        // Assert
        $this->assertSame([CpuTracker::FIELD_USER_MS => 12.345, CpuTracker::FIELD_SYS_MS => 0.678], $cpu);
    }

    /**
     * @return void
     */
    public function testUnreadableCpuIsNullNotZero(): void
    {
        // Arrange
        $cpuTracker = new ScriptedCpuTracker();
        $cpuTracker->isReadable = false;

        // Act
        $fields = CpuTracker::toRecordFields($cpuTracker->endScope($cpuTracker->beginScope()));

        // Assert
        $this->assertSame([CpuTracker::FIELD_USER_MS => null, CpuTracker::FIELD_SYS_MS => null], $fields);
    }

    /**
     * Runs against the engine: a busy loop must show up as non-negative user time.
     *
     * @return void
     */
    public function testRealScopeCpuFieldsArePresentAndNonNegative(): void
    {
        // Arrange
        $cpuTracker = new CpuTracker();
        $scope = $cpuTracker->beginScope();
        $hash = '';

        for ($i = 0; $i < 20000; $i++) {
            $hash = md5($hash . $i);
        }

        // Act
        $fields = CpuTracker::toRecordFields($cpuTracker->endScope($scope));

        // Assert
        $this->assertIsFloat($fields[CpuTracker::FIELD_USER_MS]);
        $this->assertIsFloat($fields[CpuTracker::FIELD_SYS_MS]);
        $this->assertGreaterThanOrEqual(0.0, $fields[CpuTracker::FIELD_USER_MS]);
        $this->assertGreaterThanOrEqual(0.0, $fields[CpuTracker::FIELD_SYS_MS]);
    }

    /**
     * @return void
     */
    public function testMergedCpuStaysNullUntilOneScopeWasMeasured(): void
    {
        // Act
        $total = CpuTracker::merge(null, null);
        $merged = CpuTracker::merge(
            CpuTracker::merge($total, [CpuTracker::FIELD_USER_MS => 1.5, CpuTracker::FIELD_SYS_MS => 0.5]),
            [CpuTracker::FIELD_USER_MS => 2.0, CpuTracker::FIELD_SYS_MS => 0.25],
        );

        // Assert
        $this->assertNull($total);
        $this->assertSame([CpuTracker::FIELD_USER_MS => 3.5, CpuTracker::FIELD_SYS_MS => 0.75], $merged);
    }
}
