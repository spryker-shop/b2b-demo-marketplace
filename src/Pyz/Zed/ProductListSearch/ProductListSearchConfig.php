<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductListSearch;

use Spryker\Shared\ProductListSearch\ProductListSearchConfig as SprykerSharedProductListSearchConfig;
use Spryker\Zed\ProductListSearch\ProductListSearchConfig as SprykerProductListSearchConfig;

class ProductListSearchConfig extends SprykerProductListSearchConfig
{
    /**
     * @return string|null
     */
    public function getEventQueueName(): ?string
    {
        return SprykerSharedProductListSearchConfig::PUBLISH_PRODUCT_LIST_SEARCH_QUEUE;
    }
}
