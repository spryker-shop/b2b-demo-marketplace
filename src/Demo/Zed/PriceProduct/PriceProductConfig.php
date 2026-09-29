<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct;

use Pyz\Zed\PriceProduct\PriceProductConfig as PyzPriceProductConfig;

class PriceProductConfig extends PyzPriceProductConfig
{
    /**
     * Perform orphan prices removing automatically.
     *
     * @var bool
     */
    protected const IS_DELETE_ORPHAN_STORE_PRICES_ON_SAVE_ENABLED = true;
}
