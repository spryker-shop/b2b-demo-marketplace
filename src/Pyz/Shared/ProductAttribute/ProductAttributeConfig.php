<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Shared\ProductAttribute;

use Spryker\Shared\ProductAttribute\ProductAttributeConfig as ProductAttributeConfigAlias;

class ProductAttributeConfig extends ProductAttributeConfigAlias
{
    /**
     * @var string
     */
    public const INPUT_TYPE_MULTISELECT = 'multiselect';
}
