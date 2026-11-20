<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\FreeShippingHint\Business\Facade;

use Codeception\Test\Unit;
use PyzTest\Zed\FreeShippingHint\FreeShippingHintBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group FreeShippingHint
 * @group Business
 * @group Facade
 * @group Facade
 * @group FreeShippingHintFacadeTest
 * Add your own group annotations below this line
 */
class FreeShippingHintFacadeTest extends Unit
{
    protected const string CURRENCY_EUR = 'EUR';

    protected const string CURRENCY_USD = 'USD';

    protected const int THRESHOLD_EUR = 100000;

    protected FreeShippingHintBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->mockConfigMethod('getFreeShippingThresholdsPerCurrency', [static::CURRENCY_EUR => static::THRESHOLD_EUR]);
    }

    public function testGivenOrderValueBelowThresholdWhenRecalculatingThenRemainingAmountIsSet(): void
    {
        // Arrange
        $calculableObjectTransfer = $this->tester->createCalculableObject(static::CURRENCY_EUR, 10739, 1074);

        // Act
        $this->tester->getFacade()->recalculateFreeShippingHint($calculableObjectTransfer);

        // Assert
        $freeShippingHintTransfer = $calculableObjectTransfer->getFreeShippingHintOrFail();
        $this->assertSame(90335, $freeShippingHintTransfer->getRemainingAmount());
        $this->assertFalse($freeShippingHintTransfer->getIsReached());
        $this->assertSame(static::THRESHOLD_EUR, $freeShippingHintTransfer->getThresholdAmount());
    }

    public function testGivenOrderValueAboveThresholdWhenRecalculatingThenThresholdIsReached(): void
    {
        // Arrange
        $calculableObjectTransfer = $this->tester->createCalculableObject(static::CURRENCY_EUR, 150000);

        // Act
        $this->tester->getFacade()->recalculateFreeShippingHint($calculableObjectTransfer);

        // Assert
        $this->assertSame(0, $calculableObjectTransfer->getFreeShippingHintOrFail()->getRemainingAmount());
        $this->assertTrue($calculableObjectTransfer->getFreeShippingHintOrFail()->getIsReached());
    }

    public function testGivenCurrencyWithoutThresholdWhenRecalculatingThenNoHintIsSet(): void
    {
        // Arrange
        $calculableObjectTransfer = $this->tester->createCalculableObject(static::CURRENCY_USD, 10000);

        // Act
        $this->tester->getFacade()->recalculateFreeShippingHint($calculableObjectTransfer);

        // Assert
        $this->assertNull($calculableObjectTransfer->getFreeShippingHint());
    }
}
