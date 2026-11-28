<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\ProductCategoryStorage\Business\Writer;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Orm\Zed\Category\Persistence\Map\SpyCategoryStoreTableMap;
use PHPUnit\Framework\MockObject\MockObject;
use Pyz\Zed\ProductCategoryStorage\Business\Trigger\ProductCategoryPublishEventTriggerInterface;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\CategoryStore\ProductCategoryStorageByCategoryStoreEventsWriter;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriter;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface;
use Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageConfig;
use Spryker\Zed\ProductCategoryStorage\Business\Reader\ProductAbstractReaderInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Reader\ProductCategoryStorageReaderInterface;
use Spryker\Zed\ProductCategoryStorage\Dependency\Facade\ProductCategoryStorageToEventBehaviorFacadeInterface;
use Spryker\Zed\ProductCategoryStorage\Dependency\Facade\ProductCategoryStorageToStoreFacadeInterface;
use Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageEntityManagerInterface;
use Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageRepositoryInterface;

/**
 * Pyz wiring of the spryker/suite#1619 fan-out (CC-31649), without a database: the writer is a partial mock
 * whose writeCollectionByBatch() records the batches it would write.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group ProductCategoryStorage
 * @group Business
 * @group Writer
 * @group ProductCategoryStorageWriterFanOutTest
 * Add your own group annotations below this line
 */
class ProductCategoryStorageWriterFanOutTest extends Unit
{
    /**
     * @var list<list<int>>
     */
    protected array $writtenBatches = [];

    /**
     * @var list<list<int>>
     */
    protected array $triggeredProductAbstractIds = [];

    public function testWriteCategoryProductAbstractCollectionWritesInPlaceWithinThreshold(): void
    {
        // Arrange
        $productCategoryStorageWriter = $this->createProductCategoryStorageWriter(3, 2);

        // Act
        $productCategoryStorageWriter->writeCategoryProductAbstractCollection([1, 2, 3]);

        // Assert
        $this->assertSame([[1, 2], [3]], $this->writtenBatches);
        $this->assertSame([], $this->triggeredProductAbstractIds);
    }

    public function testWriteCategoryProductAbstractCollectionTriggersEventsAboveThreshold(): void
    {
        // Arrange
        $productCategoryStorageWriter = $this->createProductCategoryStorageWriter(2, 2);

        // Act
        $productCategoryStorageWriter->writeCategoryProductAbstractCollection([1, 2, 3]);

        // Assert
        $this->assertSame([], $this->writtenBatches);
        $this->assertSame([[1, 2, 3]], $this->triggeredProductAbstractIds);
    }

    public function testWriteCollectionByRelatedCategoriesUsesTheBoundedPath(): void
    {
        // Arrange
        $productAbstractReaderMock = $this->createMock(ProductAbstractReaderInterface::class);
        $productAbstractReaderMock->method('getRelatedCategoryIds')->with([5])->willReturn([5, 6, 7]);
        $productAbstractReaderMock->method('getProductAbstractIdsByCategoryIds')->with([5, 6, 7])->willReturn([1, 2, 3]);
        $productCategoryStorageWriter = $this->createProductCategoryStorageWriter(2, 2, $productAbstractReaderMock);

        // Act
        $productCategoryStorageWriter->writeCollectionByRelatedCategories([5], false);

        // Assert
        $this->assertSame([], $this->writtenBatches);
        $this->assertSame([[1, 2, 3]], $this->triggeredProductAbstractIds);
    }

    public function testWriteCollectionKeepsWritingInPlaceAboveThreshold(): void
    {
        // Arrange
        $productCategoryStorageWriter = $this->createProductCategoryStorageWriter(1, 0);

        // Act
        $productCategoryStorageWriter->writeCollection([1, 2]);

        // Assert
        $this->assertSame([[1], [2]], $this->writtenBatches);
        $this->assertSame([], $this->triggeredProductAbstractIds);
    }

    public function testCategoryStoreEventsUseTheBoundedPath(): void
    {
        // Arrange
        $eventBehaviorFacadeMock = $this->createMock(ProductCategoryStorageToEventBehaviorFacadeInterface::class);
        $eventBehaviorFacadeMock->method('getEventTransferForeignKeys')
            ->with($this->anything(), SpyCategoryStoreTableMap::COL_FK_CATEGORY)
            ->willReturn([5]);
        $eventBehaviorFacadeMock->method('getEventTransferIds')->willReturn([5]);

        $productAbstractReaderMock = $this->createMock(ProductAbstractReaderInterface::class);
        $productAbstractReaderMock->method('getProductAbstractIdsByCategoryIds')->with([5])->willReturn([1, 2, 3]);

        $productCategoryStorageWriterMock = $this->createMock(ProductCategoryStorageWriterInterface::class);
        $productCategoryStorageWriterMock->expects($this->exactly(2))
            ->method('writeCategoryProductAbstractCollection')
            ->with([1, 2, 3]);
        $productCategoryStorageWriterMock->expects($this->never())->method('writeCollection');

        $productCategoryStorageByCategoryStoreEventsWriter = new ProductCategoryStorageByCategoryStoreEventsWriter(
            $eventBehaviorFacadeMock,
            $productAbstractReaderMock,
            $productCategoryStorageWriterMock,
        );

        // Act
        $productCategoryStorageByCategoryStoreEventsWriter->writeCollectionByCategoryStoreEvents([new EventEntityTransfer()]);
        $productCategoryStorageByCategoryStoreEventsWriter->writeCollectionByCategoryStorePublishingEvents([new EventEntityTransfer()]);
    }

    protected function createProductCategoryStorageWriter(
        int $threshold,
        int $writeBatchSize,
        ?ProductAbstractReaderInterface $productAbstractReader = null,
    ): ProductCategoryStorageWriter&MockObject {
        $productCategoryStorageConfigMock = $this->createMock(ProductCategoryStorageConfig::class);
        $productCategoryStorageConfigMock->method('getCategoryProductAbstractPublishThreshold')->willReturn($threshold);
        $productCategoryStorageConfigMock->method('getWriteCollectionBatchSize')->willReturn($writeBatchSize);

        $productCategoryPublishEventTriggerMock = $this->createMock(ProductCategoryPublishEventTriggerInterface::class);
        $productCategoryPublishEventTriggerMock->method('triggerProductCategoryPublishEvents')
            ->willReturnCallback(function (array $productAbstractIds): void {
                $this->triggeredProductAbstractIds[] = $productAbstractIds;
            });

        $productCategoryStorageWriterMock = $this->getMockBuilder(ProductCategoryStorageWriter::class)
            ->setConstructorArgs([
                $this->createMock(ProductCategoryStorageRepositoryInterface::class),
                $this->createMock(ProductCategoryStorageEntityManagerInterface::class),
                $this->createMock(ProductCategoryStorageToStoreFacadeInterface::class),
                $productAbstractReader ?? $this->createMock(ProductAbstractReaderInterface::class),
                $this->createMock(ProductCategoryStorageReaderInterface::class),
                $productCategoryStorageConfigMock,
                $productCategoryPublishEventTriggerMock,
            ])
            ->onlyMethods(['writeCollectionByBatch'])
            ->getMock();
        $productCategoryStorageWriterMock->method('writeCollectionByBatch')
            ->willReturnCallback(function (array $productAbstractIds): void {
                $this->writtenBatches[] = $productAbstractIds;
            });

        return $productCategoryStorageWriterMock;
    }
}
