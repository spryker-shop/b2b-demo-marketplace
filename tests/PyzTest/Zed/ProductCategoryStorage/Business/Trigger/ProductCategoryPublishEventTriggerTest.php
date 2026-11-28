<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\ProductCategoryStorage\Business\Trigger;

use Codeception\Test\Unit;
use Pyz\Zed\ProductCategoryStorage\Business\Trigger\ProductCategoryPublishEventTrigger;
use Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;

/**
 * Backport of the spryker/suite#1619 tests (CC-31649).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group ProductCategoryStorage
 * @group Business
 * @group Trigger
 * @group ProductCategoryPublishEventTriggerTest
 * Add your own group annotations below this line
 */
class ProductCategoryPublishEventTriggerTest extends Unit
{
    /**
     * @var string
     */
    protected const PRODUCT_CATEGORY_PUBLISH = 'ProductCategory.category.publish';

    /**
     * @var list<array{string, list<int>}>
     */
    protected array $triggeredEvents = [];

    public function testTriggerProductCategoryPublishEventsTriggersChunkedEventsWithAllProductAbstractIds(): void
    {
        // Arrange
        $productCategoryPublishEventTrigger = $this->createProductCategoryPublishEventTrigger(2);

        // Act
        $productCategoryPublishEventTrigger->triggerProductCategoryPublishEvents([11, 12, 13, 14, 15]);

        // Assert
        $this->assertSame(
            [
                [static::PRODUCT_CATEGORY_PUBLISH, [11, 12]],
                [static::PRODUCT_CATEGORY_PUBLISH, [13, 14]],
                [static::PRODUCT_CATEGORY_PUBLISH, [15]],
            ],
            $this->triggeredEvents,
        );
    }

    public function testTriggerProductCategoryPublishEventsUsesChunkSizeOfOneWhenConfiguredChunkSizeIsNotPositive(): void
    {
        // Arrange
        $productCategoryPublishEventTrigger = $this->createProductCategoryPublishEventTrigger(0);

        // Act
        $productCategoryPublishEventTrigger->triggerProductCategoryPublishEvents([11, 12]);

        // Assert
        $this->assertSame(
            [
                [static::PRODUCT_CATEGORY_PUBLISH, [11]],
                [static::PRODUCT_CATEGORY_PUBLISH, [12]],
            ],
            $this->triggeredEvents,
        );
    }

    public function testTriggerProductCategoryPublishEventsTriggersNothingWithoutProductAbstractIds(): void
    {
        // Arrange
        $productCategoryPublishEventTrigger = $this->createProductCategoryPublishEventTrigger(2);

        // Act
        $productCategoryPublishEventTrigger->triggerProductCategoryPublishEvents([]);

        // Assert
        $this->assertSame([], $this->triggeredEvents);
    }

    public function testConfigDefaultsAreOneThousand(): void
    {
        // Arrange
        $productCategoryStorageConfig = new ProductCategoryStorageConfig();

        // Act & Assert
        $this->assertSame(1000, $productCategoryStorageConfig->getCategoryProductAbstractPublishThreshold());
        $this->assertSame(1000, $productCategoryStorageConfig->getCategoryProductAbstractPublishEventChunkSize());
    }

    protected function createProductCategoryPublishEventTrigger(int $eventChunkSize): ProductCategoryPublishEventTrigger
    {
        $productCategoryStorageConfigMock = $this->createMock(ProductCategoryStorageConfig::class);
        $productCategoryStorageConfigMock->method('getCategoryProductAbstractPublishEventChunkSize')->willReturn($eventChunkSize);

        $eventFacadeMock = $this->createMock(EventFacadeInterface::class);
        $eventFacadeMock->method('triggerBulk')->willReturnCallback(function (string $eventName, array $eventEntityTransfers): void {
            $productAbstractIds = [];

            foreach ($eventEntityTransfers as $eventEntityTransfer) {
                $productAbstractIds[] = $eventEntityTransfer->getIdOrFail();
            }

            $this->triggeredEvents[] = [$eventName, $productAbstractIds];
        });

        return new ProductCategoryPublishEventTrigger($eventFacadeMock, $productCategoryStorageConfigMock);
    }
}
