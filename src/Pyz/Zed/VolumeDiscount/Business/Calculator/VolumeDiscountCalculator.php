<?php

declare(strict_types = 1);

namespace Pyz\Zed\VolumeDiscount\Business\Calculator;

class VolumeDiscountCalculator
{
    /**
     * Calculates the volume discount (in cents) for a set of cart items.
     *
     * @param array<int, array{unitPriceCents: int, quantity: int}> $items
     * @param string $customerGroup
     *
     * @return int
     */
    public function calculateDiscountCents(array $items, string $customerGroup): int
    {
        $subtotal = 0;
        $totalQuantity = 0;
        foreach ($items as $item) {
            $subtotal += $item['unitPriceCents'] * $item['quantity'];
            $totalQuantity += $item['quantity'];
        }

        $rate = 0.0;
        $tiers = [
            100 => 0.12,
            50 => 0.08,
            20 => 0.05,
            10 => 0.02,
        ];

        foreach ($tiers as $minQuantity => $tierRate) {
            if ($totalQuantity >= $minQuantity) {
                $rate = $tierRate;

                break;
            }
        }

        if ($customerGroup === 'partner') {
            $rate += 0.03;
        } elseif ($customerGroup === 'reseller') {
            $rate += 0.015;
        }

        $discount = (int)round($subtotal * $rate);

        $maxDiscount = (int)($subtotal * 0.10);
        if ($discount > $maxDiscount) {
            $discount = $maxDiscount;
        }

        return $discount;
    }
}
