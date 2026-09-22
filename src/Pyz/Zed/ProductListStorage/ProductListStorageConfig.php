<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductListStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\ProductListStorage\ProductListStorageConfig as SprykerSharedProductListStorageConfig;
use Spryker\Zed\ProductListStorage\ProductListStorageConfig as SprykerProductListStorageConfig;

class ProductListStorageConfig extends SprykerProductListStorageConfig
{
    public function getProductAbstractProductListSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    public function getProductConcreteProductListSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    public function getProductAbstractProductListEventQueueName(): ?string
    {
        return SprykerSharedProductListStorageConfig::PUBLISH_PRODUCT_LIST_PRODUCT_ABSTRACT_QUEUE;
    }

    public function getProductConcreteProductListEventQueueName(): ?string
    {
        return SprykerSharedProductListStorageConfig::PUBLISH_PRODUCT_LIST_PRODUCT_CONCRETE_QUEUE;
    }

    public function getProductListEventQueueName(): ?string
    {
        return SprykerSharedProductListStorageConfig::PUBLISH_PRODUCT_LIST_QUEUE;
    }
}
