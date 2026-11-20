<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Listener;

use Spryker\Zed\ProductPageSearch\Communication\Plugin\Event\Listener\ProductPageCategoryNodeSearchListener as SprykerProductPageCategoryNodeSearchListener;

/**
 * @see \Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Listener\CategoryFanOutTrait
 *
 * @method \Spryker\Zed\ProductPageSearch\Communication\ProductPageSearchCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductPageSearch\Business\ProductPageSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductPageSearch\ProductPageSearchConfig getConfig()
 * @method \Spryker\Zed\ProductPageSearch\Persistence\ProductPageSearchQueryContainerInterface getQueryContainer()
 */
class ProductPageCategoryNodeSearchListener extends SprykerProductPageCategoryNodeSearchListener
{
    use CategoryFanOutTrait;
}
