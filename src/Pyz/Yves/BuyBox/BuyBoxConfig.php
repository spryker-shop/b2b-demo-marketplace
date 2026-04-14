<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\BuyBox;

use SprykerFeature\Yves\BuyBox\BuyBoxConfig as SprykerFeatureBuyBoxConfig;

class BuyBoxConfig extends SprykerFeatureBuyBoxConfig
{
    /**
     * @return string
     */
    public function getSortingStrategy(): string
    {
        return static::SORT_BY_PRICE;
    }
}
