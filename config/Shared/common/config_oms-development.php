<?php

declare(strict_types = 1);

use Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Shared\Nopayment\NopaymentConfig;
use Spryker\Shared\Oms\OmsConstants;
use Spryker\Shared\Sales\SalesConstants;
use Spryker\Shared\DummyPayment\DummyPaymentConfig;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConstants;

// ----------------------------------------------------------------------------
// ------------------------------ OMS -----------------------------------------
// ----------------------------------------------------------------------------

$config[KernelConstants::DEPENDENCY_INJECTOR_YVES] = [
    'CheckoutPage' => [
        'DummyPayment',
        'DummyMarketplacePayment',
        NopaymentConfig::PAYMENT_PROVIDER_NAME,
    ],
];
$config[KernelConstants::DEPENDENCY_INJECTOR_ZED] = [
    'Payment' => [
        'DummyPayment',
        'DummyMarketplacePayment',
        NopaymentConfig::PAYMENT_PROVIDER_NAME,
    ],
    'Oms' => [
        'DummyPayment',
    ],
];

$config[OmsConstants::ACTIVE_PROCESSES] = array_merge([
    'MarketplacePayment01',
    'DummyPayment01',
    'Nopayment01',
], $config[OmsConstants::ACTIVE_PROCESSES]);

$config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING] = array_replace(
    $config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING],
    [
        DummyPaymentConfig::PAYMENT_METHOD_INVOICE => 'DummyPayment01',
        DummyPaymentConfig::PAYMENT_METHOD_CREDIT_CARD => 'DummyPayment01',
        DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE => 'MarketplacePayment01',
        NopaymentConfig::PAYMENT_PROVIDER_NAME => 'Nopayment01',
    ],
);

$config[SelfServicePortalConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING] = $config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING];
