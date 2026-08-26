<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductBundle\Business\ProductBundle\Cart;

use Generated\Shared\Transfer\CartChangeTransfer;

interface BundledItemsProductOfferExpanderInterface
{
    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundledItemsWithProductOffer(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
