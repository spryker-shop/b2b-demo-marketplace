<?php

declare(strict_types = 1);

namespace Pyz\Zed\VolumeDiscount\Business;

use Pyz\Zed\VolumeDiscount\Business\Calculator\VolumeDiscountCalculator;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Pyz\Zed\VolumeDiscount\VolumeDiscountConfig getConfig()
 */
class VolumeDiscountBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\VolumeDiscount\Business\Calculator\VolumeDiscountCalculator
     */
    public function createVolumeDiscountCalculator(): VolumeDiscountCalculator
    {
        return new VolumeDiscountCalculator();
    }
}
