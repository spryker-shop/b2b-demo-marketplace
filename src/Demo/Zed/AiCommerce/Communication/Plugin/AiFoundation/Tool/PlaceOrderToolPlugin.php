<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \Pyz\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class PlaceOrderToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'place_order';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Place an order from the current quote. Requires all checkout data to be provided as parameters because address, shipment, and payment are not persisted in the quote. Pass the same values used in set_address, set_shipment_method, and set_payment.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                name: 'idQuote',
                type: 'integer',
                description: 'The ID of the quote to place as an order',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'salutation',
                type: 'string',
                description: 'Address salutation (Mr, Ms, Mrs, Dr)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'firstName',
                type: 'string',
                description: 'Address first name',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'lastName',
                type: 'string',
                description: 'Address last name',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'address1',
                type: 'string',
                description: 'Street address line 1',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'address2',
                type: 'string',
                description: 'Street address line 2',
                isRequired: false,
            ),
            new ToolParameter(
                name: 'city',
                type: 'string',
                description: 'City',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'zipCode',
                type: 'string',
                description: 'Zip/postal code',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'iso2Code',
                type: 'string',
                description: 'Country ISO2 code (e.g. DE)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'email',
                type: 'string',
                description: 'Email address',
                isRequired: false,
            ),
            new ToolParameter(
                name: 'phone',
                type: 'string',
                description: 'Phone number',
                isRequired: false,
            ),
            new ToolParameter(
                name: 'company',
                type: 'string',
                description: 'Company name',
                isRequired: false,
            ),
            new ToolParameter(
                name: 'idShipmentMethod',
                type: 'integer',
                description: 'The shipment method ID (e.g. 1 for Standard)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'paymentProvider',
                type: 'string',
                description: 'Payment provider name (e.g. DummyPayment)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'paymentMethod',
                type: 'string',
                description: 'Payment method name (e.g. dummyPaymentInvoice)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'dateOfBirth',
                type: 'string',
                description: 'Date of birth YYYY-MM-DD (required for invoice payment)',
                isRequired: false,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        /** @var array<string, mixed> $arguments */
        return $this->getBusinessFactory()->createPlaceOrderCheckoutManager()->placeOrder($arguments);
    }
}
