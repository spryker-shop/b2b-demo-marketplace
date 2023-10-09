<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Glue\CheckoutRestApi;

use Spryker\Glue\CheckoutRestApi\CheckoutRestApiConfig as SprykerCheckoutRestApiConfig;

class CheckoutRestApiConfig extends SprykerCheckoutRestApiConfig
{
    /**
     * @var array<string, array<string>>
     */
    protected const PAYMENT_METHOD_REQUIRED_FIELDS = [
        'dummyMarketplacePaymentInvoice' => ['dummyMarketplacePaymentInvoice.dateOfBirth'],
    ];

    /**
     * @uses \Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig::PAYMENT_PROVIDER_NAME
     *
     * @var string
     */
    protected const DUMMY_MARKETPLACE_PAYMENT_PROVIDER_NAME = 'DummyMarketplacePayment';

    /**
     * @var string
     */
    protected const DUMMY_PAYMENT_PAYMENT_METHOD_NAME_INVOICE = 'Invoice';

    /**
     * @uses \Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE
     *
     * @var string
     */
    protected const PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE = 'dummyMarketplacePaymentInvoice';

    /**
     * @var bool
     */
    protected const IS_PAYMENT_PROVIDER_METHOD_TO_STATE_MACHINE_MAPPING_ENABLED = false;

    /**
     * @return array<array<string>>
     */
    public function getPaymentProviderMethodToStateMachineMapping(): array
    {
        return [
            static::DUMMY_MARKETPLACE_PAYMENT_PROVIDER_NAME => [
                static::DUMMY_PAYMENT_PAYMENT_METHOD_NAME_INVOICE => static::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE,
            ],
        ];
    }

    /**
     * @return bool
     */
    public function isShipmentMethodsMappedToAttributes(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isPaymentProvidersMappedToAttributes(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isAddressesMappedToAttributes(): bool
    {
        return false;
    }
}
