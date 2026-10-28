<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductImage;

use Spryker\Zed\ProductImage\ProductImageConfig as SprykerProductImageConfig;

class ProductImageConfig extends SprykerProductImageConfig
{
    public function isProductImageSetUuidEnabled(): bool
    {
        return true;
    }
}
