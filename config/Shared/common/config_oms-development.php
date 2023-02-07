<?php

use Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Shared\Nopayment\NopaymentConfig;
use Spryker\Shared\Oms\OmsConstants;
use Spryker\Shared\Sales\SalesConstants;

// ----------------------------------------------------------------------------
// ------------------------------ OMS -----------------------------------------
// ----------------------------------------------------------------------------

$config[KernelConstants::DEPENDENCY_INJECTOR_YVES] = [
    'CheckoutPage' => [
        'DummyMarketplacePayment',
        NopaymentConfig::PAYMENT_PROVIDER_NAME,
    ],
];
$config[KernelConstants::DEPENDENCY_INJECTOR_ZED] = [
    'Payment' => [
        'DummyMarketplacePayment',
        NopaymentConfig::PAYMENT_PROVIDER_NAME,
    ],
    'Oms' => [
        'DummyPayment',
    ],
];

$config[OmsConstants::ACTIVE_PROCESSES] = array_merge([
    'MarketplacePayment01',
    'Nopayment01',
], $config[OmsConstants::ACTIVE_PROCESSES]);

$config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING] = array_replace(
    $config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING],
    [
        DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE => 'MarketplacePayment01',
        NopaymentConfig::PAYMENT_PROVIDER_NAME => 'Nopayment01',
    ],
);
