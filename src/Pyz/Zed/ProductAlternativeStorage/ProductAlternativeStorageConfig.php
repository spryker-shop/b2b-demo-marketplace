<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductAlternativeStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\ProductAlternativeStorage\ProductAlternativeStorageConfig as SprykerSharedProductAlternativeStorageConfig;
use Spryker\Zed\ProductAlternativeStorage\ProductAlternativeStorageConfig as SprykerProductAlternativeStorageConfig;

class ProductAlternativeStorageConfig extends SprykerProductAlternativeStorageConfig
{
    /**
     * @return string|null
     */
    public function getProductAlternativeSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    /**
     * @return string|null
     */
    public function getProductReplacementForSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    /**
     * @return string|null
     */
    public function getProductAlternativeEventQueueName(): ?string
    {
        return SprykerSharedProductAlternativeStorageConfig::PUBLISH_PRODUCT_ALTERNATIVE_QUEUE;
    }

    /**
     * @return string|null
     */
    public function getProductReplacementForEventQueueName(): ?string
    {
        return SprykerSharedProductAlternativeStorageConfig::PUBLISH_PRODUCT_ALTERNATIVE_QUEUE;
    }
}
