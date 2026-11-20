<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint\Business;

use Generated\Shared\Transfer\CalculableObjectTransfer;

interface FreeShippingHintFacadeInterface
{
    /**
     * Specification:
     * - Resets `CalculableObjectTransfer.freeShippingHint`.
     * - Does nothing more when the currency has no configured free shipping threshold.
     * - Uses `Totals.subtotal` minus `Totals.discountTotal` as the order value.
     * - Sets `FreeShippingHint.remainingAmount` to the missing amount (min. 0) and `isReached` to true when nothing is missing.
     *
     * @api
     */
    public function recalculateFreeShippingHint(CalculableObjectTransfer $calculableObjectTransfer): void;
}
