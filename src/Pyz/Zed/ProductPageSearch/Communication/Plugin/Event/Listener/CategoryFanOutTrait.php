<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Zed\Kernel\Locator;

/**
 * Workaround for CC-31649: a category event fans out to every product of the category and all its
 * descendants, and the core listener renders all those product pages in one queue task. For the root
 * category that is the whole catalog, the task runs out of memory and the batch is redelivered forever.
 *
 * Above FAN_OUT_THRESHOLD products the listener sends Product.product_abstract_search.publish events in
 * chunks instead (the same pattern merchant-product-offer-search uses for merchants). Only the product page
 * search listener consumes that event, so each chunk becomes a normal page task.
 */
trait CategoryFanOutTrait
{
    /**
     * @var int
     */
    protected static int $fanOutThreshold = 1000;

    /**
     * @var int
     */
    protected static int $fanOutChunkSize = 1000;

    /**
     * @var string
     */
    protected static string $productAbstractSearchPublishEvent = 'Product.product_abstract_search.publish';

    /**
     * @param array<int, int> $productAbstractIdTimestampMap
     *
     * @return void
     */
    protected function publish(array $productAbstractIdTimestampMap)
    {
        if (count($productAbstractIdTimestampMap) <= static::$fanOutThreshold) {
            parent::publish($productAbstractIdTimestampMap);

            return;
        }

        $eventFacade = Locator::getInstance()->event()->facade();

        foreach (array_chunk(array_keys($productAbstractIdTimestampMap), static::$fanOutChunkSize) as $productAbstractIds) {
            $eventEntityTransfers = [];

            foreach ($productAbstractIds as $idProductAbstract) {
                $eventEntityTransfers[] = (new EventEntityTransfer())->setId((int)$idProductAbstract);
            }

            $eventFacade->triggerBulk(static::$productAbstractSearchPublishEvent, $eventEntityTransfers);
        }
    }
}
