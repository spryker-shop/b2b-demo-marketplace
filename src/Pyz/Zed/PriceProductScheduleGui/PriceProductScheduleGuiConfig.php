<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductScheduleGui;

use Spryker\Zed\PriceProductScheduleGui\PriceProductScheduleGuiConfig as SprykerPriceProductScheduleGuiConfig;

class PriceProductScheduleGuiConfig extends SprykerPriceProductScheduleGuiConfig
{
    /**
     * @var bool
     */
    protected const IS_FILE_EXTENSION_VALIDATION_ENABLED = true;

    protected const bool IS_GZIP_CSV_EXPORT_ENABLED = true;

    protected const int CSV_EXPORT_MAX_PRICE_COUNT = 500000;
}
