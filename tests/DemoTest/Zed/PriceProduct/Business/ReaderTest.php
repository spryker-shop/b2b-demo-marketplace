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
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Spryker\Shared\Price\PriceConfig as SprykerPriceConfig;

/**
 * @group DemoTest
 * @group Zed
 * @group PriceProduct
 * @group Business
 * @group ReaderTest
 */
class ReaderTest extends Unit
{
    protected const int GROSS_AMOUNT = 10000;

    protected const int NET_AMOUNT = 8403;

    protected const int COST_AMOUNT = 6000;

    protected const string PRICE_TYPE_NAME = 'DEFAULT';

    protected PriceProductBusinessTester $tester;

    public function testFindPriceForCostModeReturnsCostAmount(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $this->tester->haveConcretePriceWithCost($productConcreteTransfer, static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $priceProductFilterTransfer = $this->createFilter($productConcreteTransfer->getSku(), PriceConfig::PRICE_MODE_COST);

        // Act
        $price = $this->tester->getFacade()->findPriceFor($priceProductFilterTransfer);

        // Assert
        $this->assertSame(static::COST_AMOUNT, $price);
    }

    public function testFindPriceForNetModeReturnsNetAmount(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $this->tester->haveConcretePriceWithCost($productConcreteTransfer, static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $priceProductFilterTransfer = $this->createFilter($productConcreteTransfer->getSku(), SprykerPriceConfig::PRICE_MODE_NET);

        // Act
        $price = $this->tester->getFacade()->findPriceFor($priceProductFilterTransfer);

        // Assert
        $this->assertSame(static::NET_AMOUNT, $price);
    }

    public function testFindPriceForGrossModeReturnsGrossAmount(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $this->tester->haveConcretePriceWithCost($productConcreteTransfer, static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $priceProductFilterTransfer = $this->createFilter($productConcreteTransfer->getSku(), SprykerPriceConfig::PRICE_MODE_GROSS);

        // Act
        $price = $this->tester->getFacade()->findPriceFor($priceProductFilterTransfer);

        // Assert
        $this->assertSame(static::GROSS_AMOUNT, $price);
    }

    public function testConcreteWithoutCostInheritsAbstractCost(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $this->tester->haveAbstractPriceWithCost($productConcreteTransfer->getFkProductAbstract(), static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $this->tester->haveConcretePriceWithCost($productConcreteTransfer, static::GROSS_AMOUNT, static::NET_AMOUNT, null);

        // Act
        $priceProductTransfers = $this->tester->getFacade()->findProductConcretePrices(
            $productConcreteTransfer->getIdProductConcrete(),
            $productConcreteTransfer->getFkProductAbstract(),
        );

        // Assert
        $this->assertSame(static::COST_AMOUNT, $this->extractCostAmount($priceProductTransfers));
    }

    /**
     * @param array<\Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     */
    private function extractCostAmount(array $priceProductTransfers): ?int
    {
        foreach ($priceProductTransfers as $priceProductTransfer) {
            if ($priceProductTransfer->getPriceTypeName() === static::PRICE_TYPE_NAME) {
                return $priceProductTransfer->getMoneyValueOrFail()->getCostAmount();
            }
        }

        return null;
    }

    private function createFilter(string $sku, string $priceMode): PriceProductFilterTransfer
    {
        return (new PriceProductFilterTransfer())
            ->setSku($sku)
            ->setPriceMode($priceMode)
            ->setCurrencyIsoCode(PriceProductBusinessTester::CURRENCY_ISO_CODE)
            ->setStoreName(PriceProductBusinessTester::STORE_NAME);
    }
}
