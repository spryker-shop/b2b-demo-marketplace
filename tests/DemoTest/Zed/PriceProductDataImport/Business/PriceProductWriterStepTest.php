<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceProductDataImport\Business;

use Codeception\Test\Unit;
use Demo\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep;
use DemoTest\Zed\PriceProductDataImport\PriceProductDataImportBusinessTester;

/**
 * @group DemoTest
 * @group Zed
 * @group PriceProductDataImport
 * @group Business
 * @group PriceProductWriterStepTest
 */
class PriceProductWriterStepTest extends Unit
{
    protected const int GROSS_PRICE = 10000;

    protected const int NET_PRICE = 8403;

    protected const int COST_PRICE = 6000;

    protected PriceProductDataImportBusinessTester $tester;

    public function testExecutePersistsCostPriceToStore(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $dataSet = $this->tester->createPriceProductDataSet($productAbstractTransfer, static::GROSS_PRICE, static::NET_PRICE, static::COST_PRICE);

        // Act
        (new PriceProductWriterStep())->execute($dataSet);

        // Assert
        $costPrice = $this->tester->findStoreCostPrice($productAbstractTransfer->getIdProductAbstract());
        $this->assertSame(static::COST_PRICE, $costPrice);
    }

    public function testExecutePersistsNullCostPriceWhenCostEmpty(): void
    {
        // Arrange
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $dataSet = $this->tester->createPriceProductDataSet($productAbstractTransfer, static::GROSS_PRICE, static::NET_PRICE, null);

        // Act
        (new PriceProductWriterStep())->execute($dataSet);

        // Assert
        $costPrice = $this->tester->findStoreCostPrice($productAbstractTransfer->getIdProductAbstract());
        $this->assertNull($costPrice);
    }
}
