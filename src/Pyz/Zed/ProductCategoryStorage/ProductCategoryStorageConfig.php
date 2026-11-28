<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\Publisher\PublisherConfig;
use Spryker\Zed\ProductCategoryStorage\ProductCategoryStorageConfig as SprykerProductCategoryStorageConfig;

class ProductCategoryStorageConfig extends SprykerProductCategoryStorageConfig
{
    /**
     * Backport of spryker/suite#1619 (CC-31649).
     *
     * @var int
     */
    protected const CATEGORY_PRODUCT_ABSTRACT_PUBLISH_THRESHOLD = 1000;

    /**
     * Backport of spryker/suite#1619 (CC-31649).
     *
     * @var int
     */
    protected const CATEGORY_PRODUCT_ABSTRACT_PUBLISH_EVENT_CHUNK_SIZE = 1000;

    /**
     * Specification:
     * - Maximum number of product abstracts that a category, category node, category attribute, category URL or category store event writes in place, in the same queue task.
     * - A category event affects the product abstracts of the category and of all its descendant categories. For a top-level category that can be the whole catalog.
     * - Above this number, the product abstracts are re-enqueued as `ProductCategory.category.publish` events
     *   in chunks of {@link getCategoryProductAbstractPublishEventChunkSize()}, so each chunk is written in a queue task of its own.
     *
     * @api
     */
    public function getCategoryProductAbstractPublishThreshold(): int
    {
        return static::CATEGORY_PRODUCT_ABSTRACT_PUBLISH_THRESHOLD;
    }

    /**
     * Specification:
     * - Number of product abstracts per `ProductCategory.category.publish` bulk event
     *   when a category event exceeds {@link getCategoryProductAbstractPublishThreshold()}.
     *
     * @api
     */
    public function getCategoryProductAbstractPublishEventChunkSize(): int
    {
        return static::CATEGORY_PRODUCT_ABSTRACT_PUBLISH_EVENT_CHUNK_SIZE;
    }

    public function getProductCategorySynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    public function getEventQueueName(): ?string
    {
        return PublisherConfig::PUBLISH_QUEUE;
    }
}
