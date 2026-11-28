<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business\Trigger;

use Generated\Shared\Transfer\EventEntityTransfer;
use Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;

/**
 * Backport of spryker/suite#1619 (CC-31649).
 *
 * Re-enqueues product abstracts as product-level publish events in chunks, so each chunk is written
 * in a queue task of its own. Memory of one task grows with every product category storage entry it
 * writes, so a category event must not write the whole category subtree in one task.
 */
class ProductCategoryPublishEventTrigger implements ProductCategoryPublishEventTriggerInterface
{
    /**
     * @uses \Spryker\Shared\ProductCategoryStorage\ProductCategoryStorageConfig::PRODUCT_CATEGORY_PUBLISH
     *
     * @var string
     */
    protected const PRODUCT_CATEGORY_PUBLISH = 'ProductCategory.category.publish';

    public function __construct(
        protected EventFacadeInterface $eventFacade,
        protected ProductCategoryStorageConfig $productCategoryStorageConfig,
    ) {
    }

    /**
     * @param array<int> $productAbstractIds
     */
    public function triggerProductCategoryPublishEvents(array $productAbstractIds): void
    {
        $productAbstractIdChunks = array_chunk(
            $productAbstractIds,
            max(1, $this->productCategoryStorageConfig->getCategoryProductAbstractPublishEventChunkSize()),
        );

        foreach ($productAbstractIdChunks as $productAbstractIdChunk) {
            $this->eventFacade->triggerBulk(
                static::PRODUCT_CATEGORY_PUBLISH,
                $this->createEventEntityTransfers($productAbstractIdChunk),
            );
        }
    }

    /**
     * @param array<int> $productAbstractIds
     *
     * @return list<\Generated\Shared\Transfer\EventEntityTransfer>
     */
    protected function createEventEntityTransfers(array $productAbstractIds): array
    {
        $eventEntityTransfers = [];

        foreach ($productAbstractIds as $idProductAbstract) {
            $eventEntityTransfers[] = (new EventEntityTransfer())->setId((int)$idProductAbstract);
        }

        return $eventEntityTransfers;
    }
}
