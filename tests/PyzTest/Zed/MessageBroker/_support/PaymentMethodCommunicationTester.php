<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker;

use Codeception\Actor;
use Generated\Shared\DataBuilder\MessageAttributesBuilder;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Spryker\Zed\Payment\Business\Generator\PaymentMethodKeyGenerator;
use Spryker\Zed\Payment\Dependency\Service\PaymentToUtilTextServiceBridge;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(\PyzTest\Zed\MessageBroker\PHPMD)
 */
class PaymentMethodCommunicationTester extends Actor
{
    use _generated\PaymentMethodCommunicationTesterActions {
        haveAddPaymentMethodTransfer as protected testerHaveAddPaymentMethodTransferAction;
        haveDeletePaymentMethodTransfer as protected testerHaveDeletePaymentMethodTransferAction;
    }

    /**
     * @param array<string, mixed> $seedData
     * @param array<string, mixed> $messageAttributesSeedData
     */
    public function haveAddPaymentMethodTransfer(
        array $seedData,
        array $messageAttributesSeedData = [],
    ): AddPaymentMethodTransfer {
        return $this->testerHaveAddPaymentMethodTransferAction($seedData)
            ->setMessageAttributes(
                (new MessageAttributesBuilder($messageAttributesSeedData))->build(),
            );
    }

    /**
     * @param array<string, mixed> $seedData
     * @param array<string, mixed> $messageAttributesSeedData
     */
    public function haveDeletePaymentMethodTransfer(
        array $seedData,
        array $messageAttributesSeedData = [],
    ): DeletePaymentMethodTransfer {
        return $this->testerHaveDeletePaymentMethodTransferAction($seedData)
            ->setMessageAttributes(
                (new MessageAttributesBuilder($messageAttributesSeedData))->build(),
            );
    }

    public function generatePaymentMethodKey(
        string $paymentProviderName,
        string $paymentMethodName,
    ): string {
        $utilTextService = $this->getLocator()->utilText()->service();
        $paymentMethodKeyGenerator = new PaymentMethodKeyGenerator(
            new PaymentToUtilTextServiceBridge($utilTextService),
        );

        return $paymentMethodKeyGenerator->generate(
            $paymentProviderName,
            $paymentMethodName,
        );
    }


}
