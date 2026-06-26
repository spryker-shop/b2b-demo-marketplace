<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Client\PriceProduct;

use Codeception\Test\Unit;
use Demo\Shared\Price\PriceConfig;
use Spryker\Client\Session\SessionClient;
use Spryker\Shared\Price\PriceConfig as SprykerPriceConfig;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @group DemoTest
 * @group Client
 * @group PriceProduct
 * @group ProductPriceResolverTest
 */
class ProductPriceResolverTest extends Unit
{
    protected const int GROSS_AMOUNT = 12000;

    protected const int NET_AMOUNT = 10000;

    protected const int COST_AMOUNT = 6000;

    protected const int EXPECTED_GROSS_MARGIN = 40;

    protected PriceProductClientTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapQuoteSession();
    }

    public function testResolveProductPriceTransferComputesGrossMarginFromNetAndCost(): void
    {
        // Arrange
        $priceProductTransfer = $this->tester->createPriceProductTransfer(static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $priceProductFilterTransfer = $this->tester->createFilter(SprykerPriceConfig::PRICE_MODE_NET);

        // Act
        $currentProductPriceTransfer = $this->tester->getClient()
            ->resolveProductPriceTransferByPriceProductFilter([$priceProductTransfer], $priceProductFilterTransfer);

        // Assert
        $this->assertSame(static::EXPECTED_GROSS_MARGIN, $currentProductPriceTransfer->getGrossMargin());
    }

    public function testResolveProductPriceTransferReturnsZeroGrossMarginWhenCostMissing(): void
    {
        // Arrange
        $priceProductTransfer = $this->tester->createPriceProductTransfer(static::GROSS_AMOUNT, static::NET_AMOUNT, null);
        $priceProductFilterTransfer = $this->tester->createFilter(SprykerPriceConfig::PRICE_MODE_NET);

        // Act
        $currentProductPriceTransfer = $this->tester->getClient()
            ->resolveProductPriceTransferByPriceProductFilter([$priceProductTransfer], $priceProductFilterTransfer);

        // Assert
        $this->assertSame(0, $currentProductPriceTransfer->getGrossMargin());
    }

    public function testResolveProductPriceTransferReturnsCostAmountForCostMode(): void
    {
        // Arrange
        $priceProductTransfer = $this->tester->createPriceProductTransfer(static::GROSS_AMOUNT, static::NET_AMOUNT, static::COST_AMOUNT);
        $priceProductFilterTransfer = $this->tester->createFilter(PriceConfig::PRICE_MODE_COST);

        // Act
        $currentProductPriceTransfer = $this->tester->getClient()
            ->resolveProductPriceTransferByPriceProductFilter([$priceProductTransfer], $priceProductFilterTransfer);

        // Assert
        $this->assertSame(static::COST_AMOUNT, $currentProductPriceTransfer->getPrice());
    }

    /**
     * The resolver eagerly reads the current price mode and currency from the quote session
     * before the filter fallbacks apply, so the session container must be initialized.
     */
    protected function bootstrapQuoteSession(): void
    {
        $sessionClient = new SessionClient();
        $sessionClient->setContainer(new Session(new MockArraySessionStorage()));
    }
}
