<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceCartConnector\Business;

use Codeception\Test\Unit;
use DemoTest\Zed\PriceCartConnector\PriceCartConnectorBusinessTester;

/**
 * @group DemoTest
 * @group Zed
 * @group PriceCartConnector
 * @group Business
 * @group PriceManagerTest
 */
class PriceManagerTest extends Unit
{
    protected const int GROSS_AMOUNT = 10000;

    protected const int NET_AMOUNT = 8403;

    protected const int COST_AMOUNT = 6000;

    protected const int EXPECTED_GROSS_MARGIN = 40;

    protected PriceCartConnectorBusinessTester $tester;

    public function testAddPriceToItemsSetsGrossMarginFromGrossAndCostPrices(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProductWithPrice(static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $cartChangeTransfer = $this->tester->createCartChangeForSku($productConcreteTransfer->getSku());

        // Act
        $cartChangeTransfer = $this->tester->getFacade()->addPriceToItems($cartChangeTransfer);

        // Assert
        $itemTransfer = $cartChangeTransfer->getItems()->offsetGet(0);
        $this->assertSame(static::EXPECTED_GROSS_MARGIN, $itemTransfer->getGrossMargin());
        $this->assertSame(static::COST_AMOUNT, $itemTransfer->getUnitCostPrice());
    }

    public function testAddPriceToItemsSetsZeroGrossMarginWhenCostMissing(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProductWithPrice(static::GROSS_AMOUNT, static::NET_AMOUNT, null);
        $cartChangeTransfer = $this->tester->createCartChangeForSku($productConcreteTransfer->getSku());

        // Act
        $cartChangeTransfer = $this->tester->getFacade()->addPriceToItems($cartChangeTransfer);

        // Assert
        $itemTransfer = $cartChangeTransfer->getItems()->offsetGet(0);
        $this->assertSame(0, $itemTransfer->getGrossMargin());
    }
}
