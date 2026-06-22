<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\PaymentsRestApi;

use Spryker\Shared\DummyPayment\DummyPaymentConfig;

use Spryker\Glue\PaymentsRestApi\PaymentsRestApiConfig as SprykerPaymentsRestApiConfig;
class PaymentsRestApiConfig extends SprykerPaymentsRestApiConfig
{
    protected const PAYMENT_METHOD_PRIORITY = [
        DummyPaymentConfig::PAYMENT_METHOD_INVOICE => 1,
    ];

    protected const PAYMENT_METHOD_REQUIRED_FIELDS = [
        DummyPaymentConfig::PAYMENT_METHOD_INVOICE => [
            'dummyPaymentInvoice.dateOfBirth',
        ],
    ];
}
