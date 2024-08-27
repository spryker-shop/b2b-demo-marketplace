<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Zed\ProductSearch\Business;

use Pyz\Zed\ProductSearch\Business\Map\ProductSearchAttributeMapper;
use Spryker\Zed\ProductSearch\Business\Map\ProductSearchAttributeMapperInterface;
use Spryker\Zed\ProductSearch\Business\ProductSearchBusinessFactory as SprykerProductSearchBusinessFactory;

/**
 * @method \Spryker\Zed\ProductSearch\Persistence\ProductSearchRepositoryInterface getRepository()
 * @method \Spryker\Zed\ProductSearch\ProductSearchConfig getConfig()
 * @method \Spryker\Zed\ProductSearch\Persistence\ProductSearchQueryContainerInterface getQueryContainer()
 */
class ProductSearchBusinessFactory extends SprykerProductSearchBusinessFactory
{
    public function createProductSearchAttributeMapper(): ProductSearchAttributeMapperInterface
    {
        return new ProductSearchAttributeMapper($this->getAttributeMapCollectors());
    }
}
