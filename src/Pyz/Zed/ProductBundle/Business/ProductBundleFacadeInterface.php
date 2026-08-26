<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductBundle\Business;

use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Zed\ProductBundle\Business\ProductBundleFacadeInterface as SprykerProductBundleFacadeInterface;

interface ProductBundleFacadeInterface extends SprykerProductBundleFacadeInterface
{
    /**
     * Specification:
     * - Expects `CartChangeTransfer.quote` to be set.
     * - Iterates over bundled items (items with `ItemTransfer.relatedBundleItemIdentifier`) that have no `ItemTransfer.productOfferReference`.
     * - Resolves the merchant of the bundle from the related bundle item in `QuoteTransfer.bundleItems`.
     * - Looks up an active, approved product offer of that merchant for each bundled item SKU.
     * - Sets `ItemTransfer.productOfferReference` and `ItemTransfer.merchantReference` on bundled items with a matching offer.
     * - Leaves bundled items without a matching offer unchanged.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandBundledItemsWithProductOffer(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
