<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductSearchConfigStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\ProductSearchConfigStorage\ProductSearchConfigStorageConfig as SprykerSharedProductSearchConfigStorageConfig;
use Spryker\Zed\ProductSearchConfigStorage\ProductSearchConfigStorageConfig as SprykerProductSearchConfigStorageConfig;

class ProductSearchConfigStorageConfig extends SprykerProductSearchConfigStorageConfig
{
    /**
     * @return string|null
     */
    public function getProductSearchConfigSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    /**
     * @return string|null
     */
    public function getEventQueueName(): ?string
    {
        return SprykerSharedProductSearchConfigStorageConfig::PUBLISH_PRODUCT_SEARCH_CONFIG_QUEUE;
    }
}
