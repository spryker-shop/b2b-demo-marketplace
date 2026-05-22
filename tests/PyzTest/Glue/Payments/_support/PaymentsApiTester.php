<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Payments;

use Spryker\Glue\PaymentsRestApi\PaymentsRestApiConfig;
use SprykerTest\Glue\Testify\Tester\ApiEndToEndTester;

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
 * @SuppressWarnings(PHPMD)
 */
class PaymentsApiTester extends ApiEndToEndTester
{
    use _generated\PaymentsApiTesterActions;

    public function buildPaymentsUrl(): string
    {
        return $this->formatFullUrl(
            '{resourcePayments}',
            [
                'resourcePayments' => PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS,
            ],
        );
    }

    public function buildPaymentCancellationsUrl(): string
    {
        return $this->formatFullUrl(
            '{resourcePaymentCancellations}',
            [
                'resourcePaymentCancellations' => PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS,
            ],
        );
    }

    public function buildPaymentCustomersUrl(): string
    {
        return $this->formatFullUrl(
            '{resourcePaymentCustomers}',
            [
                'resourcePaymentCustomers' => PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS,
            ],
        );
    }
}
