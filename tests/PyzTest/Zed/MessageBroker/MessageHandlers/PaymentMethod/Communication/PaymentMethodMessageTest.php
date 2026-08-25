<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker\MessageHandlers\PaymentMethod\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use PyzTest\Zed\MessageBroker\PaymentMethodCommunicationTester;
use Spryker\Zed\MessageBroker\MessageBrokerDependencyProvider;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group MessageBroker
 * @group MessageHandlers
 * @group PaymentMethod
 * @group Communication
 * @group PaymentMethodMessageTest
 * Add your own group annotations below this line
 */
class PaymentMethodMessageTest extends Unit
{
    protected const string PAYMENT_METHOD_NAME = 'payment-method-name';

    protected const string PROVIDER_NAME = 'provider-name';

    protected const string CHANNEL_NAME = 'payment-method-commands';

    protected PaymentMethodCommunicationTester $tester;

    public function testGivenNoPaymentMethodExistsWhenTheAddPaymentMethodMessageIsHandledThenThePaymentMethodIsCreatedAndVisible(): void
    {
        // Arrange
        $paymentMethodKey = $this->tester->generatePaymentMethodKey(
            static::PROVIDER_NAME,
            static::PAYMENT_METHOD_NAME,
        );
        $this->tester->setupMessageBroker(AddPaymentMethodTransfer::class, static::CHANNEL_NAME);
        $this->tester->setDependency(MessageBrokerDependencyProvider::PLUGINS_EXTERNAL_VALIDATOR, []);
        $this->tester->setDependency(MessageBrokerDependencyProvider::PLUGINS_FILTER_MESSAGE_CHANNEL, []);
        $messageBrokerFacade = $this->tester->getLocator()->messageBroker()->facade();

        // Act
        $messageBrokerFacade->sendMessage(
            $this->tester->haveAddPaymentMethodTransfer([
                DeletePaymentMethodTransfer::NAME => static::PAYMENT_METHOD_NAME,
                DeletePaymentMethodTransfer::PROVIDER_NAME => static::PROVIDER_NAME,
            ]),
        );
        $messageBrokerFacade->startWorker(
            $this->tester->buildMessageBrokerWorkerConfigTransfer([static::CHANNEL_NAME], 1),
        );

        // Assert
        $paymentMethodTransfer = $this->tester->findPaymentMethod(
            (new PaymentMethodTransfer())->setPaymentMethodKey($paymentMethodKey),
        );
        $this->assertNotNull($paymentMethodTransfer);
        $this->assertSame(static::PAYMENT_METHOD_NAME, $paymentMethodTransfer->getName());
        $this->assertFalse($paymentMethodTransfer->getIsHidden());
    }

    public function testGivenAnAddedPaymentMethodWhenTheDeletePaymentMethodMessageIsHandledThenThePaymentMethodIsHidden(): void
    {
        // Arrange
        $paymentMethodKey = $this->tester->generatePaymentMethodKey(
            static::PROVIDER_NAME,
            static::PAYMENT_METHOD_NAME,
        );
        $this->tester->setupMessageBroker(AddPaymentMethodTransfer::class, static::CHANNEL_NAME);
        $this->tester->setDependency(MessageBrokerDependencyProvider::PLUGINS_EXTERNAL_VALIDATOR, []);
        $this->tester->setDependency(MessageBrokerDependencyProvider::PLUGINS_FILTER_MESSAGE_CHANNEL, []);
        $messageBrokerFacade = $this->tester->getLocator()->messageBroker()->facade();
        $messageBrokerWorkerConfigTransfer = $this->tester->buildMessageBrokerWorkerConfigTransfer(
            [static::CHANNEL_NAME],
            1,
        );
        $messageBrokerFacade->sendMessage(
            $this->tester->haveAddPaymentMethodTransfer([
                DeletePaymentMethodTransfer::NAME => static::PAYMENT_METHOD_NAME,
                DeletePaymentMethodTransfer::PROVIDER_NAME => static::PROVIDER_NAME,
            ]),
        );
        $messageBrokerFacade->startWorker($messageBrokerWorkerConfigTransfer);
        $this->tester->resetInMemoryMessages();

        // Act
        $this->tester->setupMessageBroker(DeletePaymentMethodTransfer::class, static::CHANNEL_NAME);
        $messageBrokerFacade->sendMessage(
            $this->tester->haveDeletePaymentMethodTransfer([
                DeletePaymentMethodTransfer::NAME => static::PAYMENT_METHOD_NAME,
                DeletePaymentMethodTransfer::PROVIDER_NAME => static::PROVIDER_NAME,
            ]),
        );
        $messageBrokerFacade->startWorker($messageBrokerWorkerConfigTransfer);

        // Assert
        $paymentMethodTransfer = $this->tester->findPaymentMethod(
            (new PaymentMethodTransfer())->setPaymentMethodKey($paymentMethodKey),
        );
        $this->assertNotNull($paymentMethodTransfer);
        $this->assertTrue($paymentMethodTransfer->getIsHidden());
    }
}
