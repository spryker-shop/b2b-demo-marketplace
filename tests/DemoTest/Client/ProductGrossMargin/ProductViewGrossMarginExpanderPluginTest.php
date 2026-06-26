<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Client\ProductGrossMargin;

use Codeception\Test\Unit;
use Demo\Client\ProductGrossMargin\Plugin\ProductStorage\ProductViewGrossMarginExpanderPlugin;
use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;

/**
 * @group DemoTest
 * @group Client
 * @group ProductGrossMargin
 * @group ProductViewGrossMarginExpanderPluginTest
 */
class ProductViewGrossMarginExpanderPluginTest extends Unit
{
    protected const string LOCALE_NAME = 'de_DE';

    protected const int GROSS_MARGIN = 42;

    protected ProductGrossMarginClientTester $tester;

    public function testExpandProductViewTransferCopiesGrossMarginFromCurrentProductPrice(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())->setCurrentProductPrice(
            (new CurrentProductPriceTransfer())->setGrossMargin(static::GROSS_MARGIN),
        );

        // Act
        $resultProductViewTransfer = (new ProductViewGrossMarginExpanderPlugin())
            ->expandProductViewTransfer($productViewTransfer, [], static::LOCALE_NAME);

        // Assert
        $this->assertSame(static::GROSS_MARGIN, $resultProductViewTransfer->getGrossMargin());
    }

    public function testExpandProductViewTransferDefaultsToZeroWhenCurrentProductPriceMissing(): void
    {
        // Arrange
        $productViewTransfer = new ProductViewTransfer();

        // Act
        $resultProductViewTransfer = (new ProductViewGrossMarginExpanderPlugin())
            ->expandProductViewTransfer($productViewTransfer, [], static::LOCALE_NAME);

        // Assert
        $this->assertSame(0, $resultProductViewTransfer->getGrossMargin());
    }
}
