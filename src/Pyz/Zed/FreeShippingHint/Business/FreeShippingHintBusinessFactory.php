<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint\Business;

use Pyz\Zed\FreeShippingHint\Business\Calculator\FreeShippingHintCalculator;
use Pyz\Zed\FreeShippingHint\Business\Calculator\FreeShippingHintCalculatorInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Pyz\Zed\FreeShippingHint\FreeShippingHintConfig getConfig()
 */
class FreeShippingHintBusinessFactory extends AbstractBusinessFactory
{
    public function createFreeShippingHintCalculator(): FreeShippingHintCalculatorInterface
    {
        return new FreeShippingHintCalculator($this->getConfig());
    }
}
