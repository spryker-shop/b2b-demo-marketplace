<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceProduct;

use Codeception\Actor;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;

/**
 * Inherited Methods
 *
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
 * @method \Spryker\Zed\PriceProduct\Business\PriceProductFacadeInterface getFacade()
 *
 * @SuppressWarnings(\DemoTest\Zed\PriceProduct\PHPMD)
 */
class PriceProductBusinessTester extends Actor
{
    use _generated\PriceProductBusinessTesterActions;

    public const string STORE_NAME = 'DE';

    public const string CURRENCY_ISO_CODE = 'EUR';

    public function haveConcretePriceWithCost(
        ProductConcreteTransfer $productConcreteTransfer,
        int $grossAmount,
        int $netAmount,
        ?int $costAmount,
    ): PriceProductTransfer {
        return $this->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSku(),
            PriceProductTransfer::SKU_PRODUCT => $productConcreteTransfer->getSku(),
            PriceProductTransfer::ID_PRODUCT => $productConcreteTransfer->getIdProductConcrete(),
            PriceProductTransfer::ID_PRODUCT_ABSTRACT => $productConcreteTransfer->getFkProductAbstract(),
            PriceProductTransfer::MONEY_VALUE => $this->buildMoneyValueSeed($grossAmount, $netAmount, $costAmount),
        ]);
    }

    public function haveAbstractPriceWithCost(
        int $idProductAbstract,
        int $grossAmount,
        int $netAmount,
        ?int $costAmount,
    ): PriceProductTransfer {
        return $this->havePriceProductAbstract($idProductAbstract, [
            PriceProductTransfer::ID_PRODUCT_ABSTRACT => $idProductAbstract,
            PriceProductTransfer::MONEY_VALUE => $this->buildMoneyValueSeed($grossAmount, $netAmount, $costAmount),
        ]);
    }

    /**
     * @param int $grossAmount
     * @param int $netAmount
     * @param int|null $costAmount
     *
     * @return array<string, int|null>
     */
    private function buildMoneyValueSeed(int $grossAmount, int $netAmount, ?int $costAmount): array
    {
        return [
            MoneyValueTransfer::GROSS_AMOUNT => $grossAmount,
            MoneyValueTransfer::NET_AMOUNT => $netAmount,
            MoneyValueTransfer::COST_AMOUNT => $costAmount,
        ];
    }
}
