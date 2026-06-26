<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceProduct\Business;

use Codeception\Test\Unit;
use DemoTest\Zed\PriceProduct\PriceProductBusinessTester;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStoreQuery;

/**
 * @group DemoTest
 * @group Zed
 * @group PriceProduct
 * @group Business
 * @group PriceProductStoreWriterTest
 */
class PriceProductStoreWriterTest extends Unit
{
    protected const int GROSS_AMOUNT = 10000;

    protected const int NET_AMOUNT = 8403;

    protected const int COST_AMOUNT = 6000;

    protected PriceProductBusinessTester $tester;

    public function testCreatePriceForProductPersistsCostPriceToStore(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();

        // Act
        $priceProductTransfer = $this->tester->haveConcretePriceWithCost(
            $productConcreteTransfer,
            static::GROSS_AMOUNT,
            static::NET_AMOUNT,
            static::COST_AMOUNT,
        );

        // Assert
        $costPrice = $this->findStoreCostPrice($priceProductTransfer->getIdPriceProduct());
        $this->assertSame(static::COST_AMOUNT, $costPrice);
    }

    public function testCreatePriceForProductWithoutCostPersistsNullCostPrice(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();

        // Act
        $priceProductTransfer = $this->tester->haveConcretePriceWithCost(
            $productConcreteTransfer,
            static::GROSS_AMOUNT,
            static::NET_AMOUNT,
            null,
        );

        // Assert
        $costPrice = $this->findStoreCostPrice($priceProductTransfer->getIdPriceProduct());
        $this->assertNull($costPrice);
    }

    private function findStoreCostPrice(int $idPriceProduct): ?int
    {
        $priceProductStoreEntity = SpyPriceProductStoreQuery::create()
            ->findOneByFkPriceProduct($idPriceProduct);

        return $priceProductStoreEntity?->getCostPrice();
    }
}
