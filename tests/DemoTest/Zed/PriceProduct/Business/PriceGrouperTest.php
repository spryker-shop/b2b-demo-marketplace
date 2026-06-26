<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceProduct\Business;

use Codeception\Test\Unit;
use Demo\Shared\Price\PriceConfig;
use DemoTest\Zed\PriceProduct\PriceProductBusinessTester;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;

/**
 * @group DemoTest
 * @group Zed
 * @group PriceProduct
 * @group Business
 * @group PriceGrouperTest
 */
class PriceGrouperTest extends Unit
{
    protected const string CURRENCY_CODE = 'EUR';

    protected const string PRICE_TYPE_NAME = 'DEFAULT';

    protected const int GROSS_AMOUNT = 10000;

    protected const int NET_AMOUNT = 8403;

    protected const int COST_AMOUNT = 6000;

    protected PriceProductBusinessTester $tester;

    public function testGroupPriceProductCollectionIncludesCostMode(): void
    {
        // Arrange
        $priceProductTransfer = $this->createPriceProductTransfer(static::COST_AMOUNT);

        // Act
        $groupedPrices = $this->tester->getFacade()->groupPriceProductCollection([$priceProductTransfer]);

        // Assert
        $this->assertSame(
            static::COST_AMOUNT,
            $groupedPrices[static::CURRENCY_CODE][PriceConfig::PRICE_MODE_COST][static::PRICE_TYPE_NAME],
        );
    }

    public function testGroupPriceProductCollectionOmitsCostModeWhenCostMissing(): void
    {
        // Arrange
        $priceProductTransfer = $this->createPriceProductTransfer(null);

        // Act
        $groupedPrices = $this->tester->getFacade()->groupPriceProductCollection([$priceProductTransfer]);

        // Assert
        $this->assertArrayNotHasKey(PriceConfig::PRICE_MODE_COST, $groupedPrices[static::CURRENCY_CODE]);
    }

    private function createPriceProductTransfer(?int $costAmount): PriceProductTransfer
    {
        $moneyValueTransfer = (new MoneyValueTransfer())
            ->setGrossAmount(static::GROSS_AMOUNT)
            ->setNetAmount(static::NET_AMOUNT)
            ->setCostAmount($costAmount)
            ->setCurrency((new CurrencyTransfer())->setCode(static::CURRENCY_CODE));

        return (new PriceProductTransfer())
            ->setPriceType((new PriceTypeTransfer())->setName(static::PRICE_TYPE_NAME))
            ->setMoneyValue($moneyValueTransfer);
    }
}
