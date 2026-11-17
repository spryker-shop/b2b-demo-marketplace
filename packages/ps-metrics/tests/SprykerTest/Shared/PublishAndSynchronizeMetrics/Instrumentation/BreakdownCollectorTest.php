<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group BreakdownCollectorTest
 * Add your own group annotations below this line
 */
class BreakdownCollectorTest extends Unit
{
    /**
     * @var list<string>
     */
    protected const array C3_FIELDS = ['db_ms', 'db_calls', 'kv_ms', 'kv_calls', 'search_ms', 'search_calls', 'broker_ms', 'broker_calls'];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        BreakdownCollector::reset();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testBuildsExactlyTheC3Fields(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $token = $breakdownCollector->openSpan();

        // Act
        $breakdown = $breakdownCollector->closeSpan($token);

        // Assert
        $this->assertSame(static::C3_FIELDS, array_keys((array)$breakdown));
    }

    /**
     * @return void
     */
    public function testAttributesACallToTheInnermostOpenSpanOnly(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $outerToken = $breakdownCollector->openSpan();
        $firstChildToken = $breakdownCollector->openSpan();
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_DB);
        $firstChild = $breakdownCollector->closeSpan($firstChildToken);
        $secondChildToken = $breakdownCollector->openSpan();
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_KV);

        // Act
        $secondChild = $breakdownCollector->closeSpan($secondChildToken);
        $breakdownCollector->closeSpan($outerToken);

        // Assert
        $this->assertSame(1, $firstChild['db_calls']);
        $this->assertSame(0, $firstChild['kv_calls']);
        $this->assertSame(0, $secondChild['db_calls']);
        $this->assertSame(1, $secondChild['kv_calls']);
    }

    /**
     * @return void
     */
    public function testParentIncludesItsChildrensCallsAndTimeAndItsOwn(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $outerToken = $breakdownCollector->openSpan();
        $childToken = $breakdownCollector->openSpan();
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_SEARCH, 2000);
        $child = $breakdownCollector->closeSpan($childToken);
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_SEARCH, 1000);

        // Act
        $outer = $breakdownCollector->closeSpan($outerToken);

        // Assert
        $this->assertSame(1, $child['search_calls']);
        $this->assertSame(2, $outer['search_calls']);
        $this->assertGreaterThanOrEqual(2.0, $child['search_ms']);
        $this->assertGreaterThanOrEqual($child['search_ms'] + 1.0, $outer['search_ms']);
    }

    /**
     * @return void
     */
    public function testReportsNullNotZeroForACategoryWhoseHookWasNotInstalled(): void
    {
        // Arrange
        $breakdownCollector = new BreakdownCollector();
        $breakdownCollector->markInstalled(BreakdownCollector::CATEGORY_DB);
        $breakdownCollector->enable();
        $token = $breakdownCollector->openSpan();

        // Act
        $breakdown = $breakdownCollector->closeSpan($token);

        // Assert
        $this->assertSame(0, $breakdown['db_calls']);
        $this->assertSame(0.0, $breakdown['db_ms']);
        $this->assertNull($breakdown['kv_ms']);
        $this->assertNull($breakdown['kv_calls']);
        $this->assertNull($breakdown['search_ms']);
        $this->assertNull($breakdown['broker_calls']);
    }

    /**
     * @return void
     */
    public function testRecordsNothingForACallMadeWhileNoSpanIsOpen(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_BROKER);
        $token = $breakdownCollector->openSpan();

        // Act
        $breakdown = $breakdownCollector->closeSpan($token);

        // Assert
        $this->assertSame(0, $breakdown['broker_calls']);
        $this->assertSame(0.0, $breakdown['broker_ms']);
    }

    /**
     * @return void
     */
    public function testRecordsNothingForACallMadeBetweenTwoTopLevelSpans(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $breakdownCollector->closeSpan($breakdownCollector->openSpan());
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_DB);
        $token = $breakdownCollector->openSpan();

        // Act
        $breakdown = $breakdownCollector->closeSpan($token);

        // Assert
        $this->assertSame(0, $breakdown['db_calls']);
    }

    /**
     * @return void
     */
    public function testReturnsANullBreakdownWhenTheBreakdownIsNotEnabled(): void
    {
        // Arrange
        $breakdownCollector = new BreakdownCollector();
        $breakdownCollector->markInstalled(BreakdownCollector::CATEGORY_DB);

        // Act
        $token = $breakdownCollector->openSpan();

        // Assert
        $this->assertNull($token);
        $this->assertNull($breakdownCollector->closeSpan($token));
    }

    /**
     * Elastica calls request() again on the next connection after a connection failure.
     *
     * @return void
     */
    public function testCountsAReentrantCallOfOneCategoryOnce(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $token = $breakdownCollector->openSpan();
        $breakdownCollector->beginCall(BreakdownCollector::CATEGORY_SEARCH);
        $breakdownCollector->beginCall(BreakdownCollector::CATEGORY_SEARCH);
        $breakdownCollector->endCall(BreakdownCollector::CATEGORY_SEARCH);
        $breakdownCollector->endCall(BreakdownCollector::CATEGORY_SEARCH);

        // Act
        $breakdown = $breakdownCollector->closeSpan($token);

        // Assert
        $this->assertSame(1, $breakdown['search_calls']);
    }

    /**
     * @return void
     */
    public function testFoldsAChildSpanLeftOpenIntoTheSpanBeingClosed(): void
    {
        // Arrange
        $breakdownCollector = $this->createCollector();
        $outerToken = $breakdownCollector->openSpan();
        $breakdownCollector->openSpan();
        $this->recordCall($breakdownCollector, BreakdownCollector::CATEGORY_DB);

        // Act
        $outer = $breakdownCollector->closeSpan($outerToken);
        $nextToken = $breakdownCollector->openSpan();

        // Assert
        $this->assertSame(1, $outer['db_calls']);
        $this->assertSame(1, $nextToken);
    }

    /**
     * @return void
     */
    public function testMergeSumsFieldsAndKeepsAnUnmeasuredCategoryNull(): void
    {
        // Arrange
        $first = ['db_ms' => 1.5, 'db_calls' => 2, 'kv_ms' => null, 'kv_calls' => null];
        $second = ['db_ms' => 0.5, 'db_calls' => 3, 'kv_ms' => null, 'kv_calls' => null];

        // Act
        $merged = BreakdownCollector::merge($first, $second);

        // Assert
        $this->assertSame(['db_ms' => 2.0, 'db_calls' => 5, 'kv_ms' => null, 'kv_calls' => null], $merged);
    }

    /**
     * @return void
     */
    public function testMergeOfANullBreakdownLeavesTheTotalUnchanged(): void
    {
        // Arrange
        $total = ['db_ms' => 1.5, 'db_calls' => 2];

        // Act & Assert
        $this->assertSame($total, BreakdownCollector::merge($total, null));
        $this->assertSame($total, BreakdownCollector::merge(null, $total));
        $this->assertNull(BreakdownCollector::merge(null, null));
    }

    /**
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector
     */
    protected function createCollector(): BreakdownCollector
    {
        $breakdownCollector = new BreakdownCollector();

        foreach ([BreakdownCollector::CATEGORY_DB, BreakdownCollector::CATEGORY_KV, BreakdownCollector::CATEGORY_SEARCH, BreakdownCollector::CATEGORY_BROKER] as $category) {
            $breakdownCollector->markInstalled($category);
        }

        $breakdownCollector->enable();

        return $breakdownCollector;
    }

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector $breakdownCollector
     * @param int $category
     * @param int $microseconds
     *
     * @return void
     */
    protected function recordCall(BreakdownCollector $breakdownCollector, int $category, int $microseconds = 0): void
    {
        $breakdownCollector->beginCall($category);

        if ($microseconds > 0) {
            usleep($microseconds);
        }

        $breakdownCollector->endCall($category);
    }
}
