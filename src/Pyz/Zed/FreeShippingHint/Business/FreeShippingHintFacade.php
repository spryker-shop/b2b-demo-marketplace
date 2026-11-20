<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint\Business;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\FreeShippingHint\Business\FreeShippingHintBusinessFactory getFactory()
 */
class FreeShippingHintFacade extends AbstractFacade implements FreeShippingHintFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return void
     */
    public function recalculateFreeShippingHint(CalculableObjectTransfer $calculableObjectTransfer): void
    {
        $this->getFactory()
            ->createFreeShippingHintCalculator()
            ->recalculate($calculableObjectTransfer);
    }
}
