<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint\Communication\Plugin\Calculation;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\CalculationExtension\Dependency\Plugin\CalculationPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\FreeShippingHint\Business\FreeShippingHintFacadeInterface getFacade()
 * @method \Pyz\Zed\FreeShippingHint\FreeShippingHintConfig getConfig()
 */
class FreeShippingHintCalculatorPlugin extends AbstractPlugin implements CalculationPluginInterface
{
    /**
     * {@inheritDoc}
     * - Calculates how much order value is missing to reach the free shipping threshold of the quote currency.
     * - Sets `CalculableObjectTransfer.freeShippingHint`.
     * - Must run after the subtotal and discount total calculators.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return void
     */
    public function recalculate(CalculableObjectTransfer $calculableObjectTransfer): void
    {
        $this->getFacade()->recalculateFreeShippingHint($calculableObjectTransfer);
    }
}
