<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductQuantityStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\ProductQuantityStorage\ProductQuantityStorageConfig as SprykerSharedProductQuantityStorageConfig;
use Spryker\Zed\ProductQuantityStorage\ProductQuantityStorageConfig as SprykerProductQuantityStorageConfig;

class ProductQuantityStorageConfig extends SprykerProductQuantityStorageConfig
{
    /**
     * @return string|null
     */
    public function getProductQuantitySynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    /**
     * @return string|null
     */
    public function getEventQueueName(): ?string
    {
        return SprykerSharedProductQuantityStorageConfig::PUBLISH_PRODUCT_QUANTITY_QUEUE;
    }
}
