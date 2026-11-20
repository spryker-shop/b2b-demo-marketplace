<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\FreeShippingHint\Business\Calculator;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\FreeShippingHintTransfer;
use Pyz\Zed\FreeShippingHint\FreeShippingHintConfig;

class FreeShippingHintCalculator implements FreeShippingHintCalculatorInterface
{
    public function __construct(protected FreeShippingHintConfig $freeShippingHintConfig)
    {
    }

    public function recalculate(CalculableObjectTransfer $calculableObjectTransfer): void
    {
        $calculableObjectTransfer->setFreeShippingHint(null);

        $currencyCode = $calculableObjectTransfer->getCurrency()?->getCode();
        $totalsTransfer = $calculableObjectTransfer->getTotals();
        $thresholds = $this->freeShippingHintConfig->getFreeShippingThresholdsPerCurrency();

        if ($currencyCode === null || $totalsTransfer === null || !isset($thresholds[$currencyCode])) {
            return;
        }

        $thresholdAmount = $thresholds[$currencyCode];
        $orderValue = (int)$totalsTransfer->getSubtotal() - (int)$totalsTransfer->getDiscountTotal();
        $remainingAmount = max(0, $thresholdAmount - $orderValue);

        $calculableObjectTransfer->setFreeShippingHint(
            (new FreeShippingHintTransfer())
                ->setThresholdAmount($thresholdAmount)
                ->setRemainingAmount($remainingAmount)
                ->setIsReached($remainingAmount === 0)
                ->setCurrencyCode($currencyCode),
        );
    }
}
