<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\FreeShippingHint;

use Codeception\Actor;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\TotalsTransfer;

/**
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 * @method \Pyz\Zed\FreeShippingHint\Business\FreeShippingHintFacadeInterface getFacade()
 *
 * @SuppressWarnings(\PyzTest\Zed\FreeShippingHint\PHPMD)
 */
class FreeShippingHintBusinessTester extends Actor
{
    use _generated\FreeShippingHintBusinessTesterActions;

    public function createCalculableObject(string $currencyCode, int $subtotal, int $discountTotal = 0): CalculableObjectTransfer
    {
        return (new CalculableObjectTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode($currencyCode))
            ->setTotals((new TotalsTransfer())->setSubtotal($subtotal)->setDiscountTotal($discountTotal));
    }
}
