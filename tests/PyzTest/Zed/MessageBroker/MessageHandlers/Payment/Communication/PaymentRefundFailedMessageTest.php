<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker\MessageHandlers\Payment\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
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
 * @group PaymentRefundFailedMessageTest
 * Add your own group annotations below this line
 */
class PaymentRefundFailedMessageTest extends Unit
{
    protected const string INITIAL_ITEM_STATE = 'payment refund pending';

    protected const string FINAL_ITEM_STATE = 'payment refund failed';

    protected PaymentCommunicationTester $tester;

    public function testGivenAnOrderItemIsPaymentRefundPendingWhenThePaymentRefundFailedMessageIsHandledThenTheItemBecomesPaymentRefundFailed(): void
    {
        // Arrange
        $salesOrderEntity = $this->tester->haveSalesOrder(static::INITIAL_ITEM_STATE);
        $paymentMessageTransfer = $this->tester->havePaymentMessageTransfer(
            PaymentRefundFailedTransfer::class,
            $salesOrderEntity,
        );

        // Act
        $this->tester->handlePaymentMessageTransfer($paymentMessageTransfer);

        // Assert
        $this->tester->assertOrderHasCorrectState($salesOrderEntity, static::FINAL_ITEM_STATE);
    }
}
