<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business\Writer;

use Pyz\Zed\ProductCategoryStorage\Business\Trigger\ProductCategoryPublishEventTriggerInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Reader\ProductAbstractReaderInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Reader\ProductCategoryStorageReaderInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriter as SprykerProductCategoryStorageWriter;
use Spryker\Zed\ProductCategoryStorage\Dependency\Facade\ProductCategoryStorageToStoreFacadeInterface;
use Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageEntityManagerInterface;
use Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageRepositoryInterface;
use Spryker\Zed\ProductCategoryStorage\ProductCategoryStorageConfig;

/**
 * Backport of spryker/suite#1619 (CC-31649): bounded category-to-product fan-out.
 *
 * A category, category node, category attribute, category URL or category store event affects the products of
 * the category and all its descendants, for a top-level category the whole catalog. Above the threshold the
 * products are re-enqueued in chunks instead of written in one queue task.
 *
 * @property \Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageConfig $productCategoryStorageConfig
 */
class ProductCategoryStorageWriter extends SprykerProductCategoryStorageWriter implements ProductCategoryStorageWriterInterface
{
    protected ProductCategoryPublishEventTriggerInterface $productCategoryPublishEventTrigger;

    public function __construct(
        ProductCategoryStorageRepositoryInterface $productCategoryStorageRepository,
        ProductCategoryStorageEntityManagerInterface $productCategoryStorageEntityManager,
        ProductCategoryStorageToStoreFacadeInterface $storeFacade,
        ProductAbstractReaderInterface $productAbstractReader,
        ProductCategoryStorageReaderInterface $productCategoryStorageReader,
        ProductCategoryStorageConfig $productCategoryStorageConfig,
        ProductCategoryPublishEventTriggerInterface $productCategoryPublishEventTrigger,
    ) {
        parent::__construct(
            $productCategoryStorageRepository,
            $productCategoryStorageEntityManager,
            $storeFacade,
            $productAbstractReader,
            $productCategoryStorageReader,
            $productCategoryStorageConfig,
        );

        $this->productCategoryPublishEventTrigger = $productCategoryPublishEventTrigger;
    }

    /**
     * @param array<int> $productAbstractIds
     */
    public function writeCollection(array $productAbstractIds): void
    {
        $writeCollectionBatchSize = $this->productCategoryStorageConfig->getWriteCollectionBatchSize();
        $productAbstractIdsBatchCollection = array_chunk($productAbstractIds, max(1, $writeCollectionBatchSize));

        foreach ($productAbstractIdsBatchCollection as $productAbstractIdsBatch) {
            $this->writeCollectionByBatch($productAbstractIdsBatch);
        }
    }

    /**
     * @param array<int> $categoryIds
     */
    public function writeCollectionByRelatedCategories(array $categoryIds, bool $allowEmptyCategories): void
    {
        if (!$allowEmptyCategories && $categoryIds === []) {
            return;
        }

        $relatedCategoryIds = $this->productAbstractReader->getRelatedCategoryIds($categoryIds);
        $productAbstractIds = $this->productAbstractReader
            ->getProductAbstractIdsByCategoryIds($relatedCategoryIds);

        $this->writeCategoryProductAbstractCollection($productAbstractIds);
    }

    /**
     * @param array<int> $productAbstractIds
     */
    public function writeCategoryProductAbstractCollection(array $productAbstractIds): void
    {
        if (count($productAbstractIds) <= $this->productCategoryStorageConfig->getCategoryProductAbstractPublishThreshold()) {
            $this->writeCollection($productAbstractIds);

            return;
        }

        $this->productCategoryPublishEventTrigger->triggerProductCategoryPublishEvents($productAbstractIds);
    }
}
