<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Glue\PaymentsRestApi;

use Spryker\Glue\PaymentsRestApi\PaymentsRestApiConfig as SprykerPaymentsRestApiConfig;
use Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig;

class PaymentsRestApiConfig extends SprykerPaymentsRestApiConfig
{
    protected const PAYMENT_METHOD_PRIORITY = [
        DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE => 1,
    ];

    protected const PAYMENT_METHOD_REQUIRED_FIELDS = [
        DummyMarketplacePaymentConfig::PAYMENT_PROVIDER_NAME => [
            DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE => [
                'dummyMarketplacePaymentInvoice.dateOfBirth',
            ],
        ],
    ];
}
