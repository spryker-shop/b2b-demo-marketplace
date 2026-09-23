<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker\MessageHandlers\Payment\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use PyzTest\Zed\MessageBroker\PaymentCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group MessageBroker
 * @group MessageHandlers
 * @group Payment
 * @group Communication
 * @group PaymentAuthorizedMessageTest
 * Add your own group annotations below this line
 */
class PaymentAuthorizedMessageTest extends Unit
{
    protected const string INITIAL_ITEM_STATE = 'payment pending';

    protected const string FINAL_ITEM_STATE = 'payment authorized';

    protected PaymentCommunicationTester $tester;

    public function testGivenAnOrderItemIsPaymentPendingWhenThePaymentAuthorizedMessageIsHandledThenTheItemBecomesPaymentAuthorized(): void
    {
        // Arrange
        $salesOrderEntity = $this->tester->haveSalesOrder(static::INITIAL_ITEM_STATE);
        $paymentMessageTransfer = $this->tester->havePaymentMessageTransfer(
            PaymentAuthorizedTransfer::class,
            $salesOrderEntity,
        );

        // Act
        $this->tester->handlePaymentMessageTransfer($paymentMessageTransfer);

        // Assert
        $this->tester->assertOrderHasCorrectState($salesOrderEntity, static::FINAL_ITEM_STATE);
    }
}
