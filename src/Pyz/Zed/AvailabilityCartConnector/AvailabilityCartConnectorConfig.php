<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AvailabilityCartConnector;

use Spryker\Zed\AvailabilityCartConnector\AvailabilityCartConnectorConfig as SprykerAvailabilityCartConnectorConfig;

class AvailabilityCartConnectorConfig extends SprykerAvailabilityCartConnectorConfig
{
    public function isSellableItemsCacheEnabled(): bool
    {
        return false;
    }
}
