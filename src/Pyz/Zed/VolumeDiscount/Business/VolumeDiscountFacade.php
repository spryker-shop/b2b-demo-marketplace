<?php

declare(strict_types = 1);

namespace Pyz\Zed\VolumeDiscount\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\VolumeDiscount\Business\VolumeDiscountBusinessFactory getFactory()
 */
class VolumeDiscountFacade extends AbstractFacade
{
    /**
     * Calculates the volume discount (in cents) for a set of cart items.
     *
     * @api
     *
     * @param array<int, array{unitPriceCents: int, quantity: int}> $items
     * @param string $customerGroup
     *
     * @return int
     */
    public function calculateDiscountCents(array $items, string $customerGroup): int
    {
        return $this->getFactory()
            ->createVolumeDiscountCalculator()
            ->calculateDiscountCents($items, $customerGroup);
    }
}
