<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductDiscontinuedStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\ProductDiscontinuedStorage\ProductDiscontinuedStorageConfig as SprykerSharedProductDiscontinuedStorageConfig;
use Spryker\Zed\ProductDiscontinuedStorage\ProductDiscontinuedStorageConfig as SprykerProductDiscontinuedStorageConfig;

class ProductDiscontinuedStorageConfig extends SprykerProductDiscontinuedStorageConfig
{
    /**
     * @return string|null
     */
    public function getProductDiscontinuedSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    /**
     * @return string|null
     */
    public function getEventQueueName(): ?string
    {
        return SprykerSharedProductDiscontinuedStorageConfig::PUBLISH_PRODUCT_DISCONTINUED_QUEUE;
    }
}
