<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductBundle\Business;

use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundleFacade as SprykerProductBundleFacade;

/**
 * @method \Pyz\Zed\ProductBundle\Business\ProductBundleBusinessFactory getFactory()
 * @method \Spryker\Zed\ProductBundle\Persistence\ProductBundleRepositoryInterface getRepository()
 */
class ProductBundleFacade extends SprykerProductBundleFacade implements ProductBundleFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundledItemsWithProductOffer(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        return $this->getFactory()
            ->createBundledItemsProductOfferExpander()
            ->expandBundledItemsWithProductOffer($cartChangeTransfer);
    }
}
