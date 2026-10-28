<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Tax;

use Spryker\Zed\Tax\TaxConfig as SprykerTaxConfig;

class TaxConfig extends SprykerTaxConfig
{
    public function isTaxSetUuidEnabled(): bool
    {
        return true;
    }
}
