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
    public function expandBundledItemsWithProductOffer(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
