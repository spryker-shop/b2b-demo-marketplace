<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductBundle\Business;

use Pyz\Zed\ProductBundle\Business\ProductBundle\Cart\BundledItemsProductOfferExpander;
use Pyz\Zed\ProductBundle\Business\ProductBundle\Cart\BundledItemsProductOfferExpanderInterface;
use Pyz\Zed\ProductBundle\ProductBundleDependencyProvider;
use Spryker\Zed\ProductBundle\Business\ProductBundleBusinessFactory as SprykerProductBundleBusinessFactory;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;

/**
 * @method \Pyz\Zed\ProductBundle\ProductBundleConfig getConfig()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface getRepository()
 */
class ProductBundleBusinessFactory extends SprykerProductBundleBusinessFactory
{
    public function createBundledItemsProductOfferExpander(): BundledItemsProductOfferExpanderInterface
    {
        return new BundledItemsProductOfferExpander(
            $this->getProductOfferFacade(),
        );
    }

    public function getProductOfferFacade(): ProductOfferFacadeInterface
    {
        return $this->getProvidedDependency(ProductBundleDependencyProvider::FACADE_PRODUCT_OFFER);
    }
}
