<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Client\PriceProduct;

use Codeception\Actor;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductDimensionTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Client\PriceProduct\PriceProductClientInterface;
use Spryker\Shared\PriceProduct\PriceProductConfig;

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
 *
 * @SuppressWarnings(\DemoTest\Client\PriceProduct\PHPMD)
 */
class PriceProductClientTester extends Actor
{
    use _generated\PriceProductClientTesterActions;

    public const string CURRENCY_ISO_CODE = 'EUR';

    public const string PRICE_TYPE_NAME = 'DEFAULT';

    public function createPriceProductTransfer(int $grossAmount, int $netAmount, ?int $costAmount): PriceProductTransfer
    {
        $moneyValueTransfer = (new MoneyValueTransfer())
            ->setGrossAmount($grossAmount)
            ->setNetAmount($netAmount)
            ->setCostAmount($costAmount)
            ->setCurrency((new CurrencyTransfer())->setCode(static::CURRENCY_ISO_CODE));

        return (new PriceProductTransfer())
            ->setPriceTypeName(static::PRICE_TYPE_NAME)
            ->setPriceDimension((new PriceProductDimensionTransfer())->setType(PriceProductConfig::PRICE_DIMENSION_DEFAULT))
            ->setMoneyValue($moneyValueTransfer);
    }

    public function createFilter(string $priceMode): PriceProductFilterTransfer
    {
        return (new PriceProductFilterTransfer())
            ->setPriceMode($priceMode)
            ->setPriceTypeName(static::PRICE_TYPE_NAME)
            ->setCurrencyIsoCode(static::CURRENCY_ISO_CODE)
            ->setQuantity(1);
    }

    public function getClient(): PriceProductClientInterface
    {
        return $this->getLocator()->priceProduct()->client();
    }
}
