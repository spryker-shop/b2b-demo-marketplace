<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleProductSalePage\Business;

use Pyz\Zed\ExampleProductSalePage\Business\Label\ProductAbstractRelationReader;
use Pyz\Zed\ExampleProductSalePage\Business\Label\ProductAbstractRelationReaderInterface;
use Pyz\Zed\ExampleProductSalePage\ExampleProductSalePageDependencyProvider;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\ProductLabel\Business\ProductLabelFacadeInterface;

/**
 * @method \Pyz\Zed\ExampleProductSalePage\Persistence\ExampleProductSalePageQueryContainerInterface getQueryContainer()
 * @method \Pyz\Zed\ExampleProductSalePage\ExampleProductSalePageConfig getConfig()
 */
class ExampleProductSalePageBusinessFactory extends AbstractBusinessFactory
{
    public function createProductAbstractRelationReader(): ProductAbstractRelationReaderInterface
    {
        return new ProductAbstractRelationReader($this->getQueryContainer(), $this->getConfig(), $this->getProductLabelFacade());
    }

    public function getProductLabelFacade(): ProductLabelFacadeInterface
    {
        return $this->getProvidedDependency(ExampleProductSalePageDependencyProvider::FACADE_PRODUCT_LABEL);
    }
}
