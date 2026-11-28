<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business\Writer\CategoryStore;

use Orm\Zed\Category\Persistence\Map\SpyCategoryStoreTableMap;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Reader\ProductAbstractReaderInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Writer\CategoryStore\ProductCategoryStorageByCategoryStoreEventsWriter as SprykerProductCategoryStorageByCategoryStoreEventsWriter;
use Spryker\Zed\ProductCategoryStorage\Dependency\Facade\ProductCategoryStorageToEventBehaviorFacadeInterface;

/**
 * Backport of spryker/suite#1619 (CC-31649): category store events use the bounded fan-out.
 *
 * @property \Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface $productCategoryStorageWriter
 */
class ProductCategoryStorageByCategoryStoreEventsWriter extends SprykerProductCategoryStorageByCategoryStoreEventsWriter
{
    public function __construct(
        ProductCategoryStorageToEventBehaviorFacadeInterface $eventBehaviorFacade,
        ProductAbstractReaderInterface $productAbstractReader,
        ProductCategoryStorageWriterInterface $productCategoryStorageWriter,
    ) {
        parent::__construct($eventBehaviorFacade, $productAbstractReader, $productCategoryStorageWriter);
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function writeCollectionByCategoryStoreEvents(array $eventEntityTransfers): void
    {
        $categoryIds = $this->eventBehaviorFacade->getEventTransferForeignKeys(
            $eventEntityTransfers,
            SpyCategoryStoreTableMap::COL_FK_CATEGORY,
        );

        $productAbstractIds = $this->productAbstractReader->getProductAbstractIdsByCategoryIds($categoryIds);

        $this->productCategoryStorageWriter->writeCategoryProductAbstractCollection($productAbstractIds);
    }

    /**
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     */
    public function writeCollectionByCategoryStorePublishingEvents(array $eventEntityTransfers): void
    {
        $categoryIds = $this->eventBehaviorFacade->getEventTransferIds($eventEntityTransfers);
        $productAbstractIds = $this->productAbstractReader->getProductAbstractIdsByCategoryIds($categoryIds);

        $this->productCategoryStorageWriter->writeCategoryProductAbstractCollection($productAbstractIds);
    }
}
