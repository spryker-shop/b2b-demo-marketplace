<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductOfferStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Zed\PriceProductOfferStorage\PriceProductOfferStorageConfig as SprykerPriceProductOfferStorageConfig;

class PriceProductOfferStorageConfig extends SprykerPriceProductOfferStorageConfig
{
    /**
     * @return string|null
     */
    public function getPriceProductOfferSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }
}
