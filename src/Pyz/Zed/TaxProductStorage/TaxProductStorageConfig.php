<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TaxProductStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Shared\TaxProductStorage\TaxProductStorageConfig as SprykerSharedTaxProductStorageConfig;
use Spryker\Zed\TaxProductStorage\TaxProductStorageConfig as SprykerTaxProductStorageConfig;

class TaxProductStorageConfig extends SprykerTaxProductStorageConfig
{
    public function getTaxProductSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }

    public function getEventQueueName(): ?string
    {
        return SprykerSharedTaxProductStorageConfig::PUBLISH_TAX_PRODUCT_QUEUE;
    }
}
