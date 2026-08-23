<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestInvokerInterface;
use Demo\Glue\McpCommerce\McpCommerceConfig;
use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Places the order for a cart the authorizing customer owns.
 *
 * Wraps the existing `POST /checkout` Storefront resource, whose `security:` expression
 * (`CUSTOMER_ACCESS and ROLE_CUSTOMER`) plus its own cart-ownership check are what refuse another
 * customer's cart id — this tool adds no ownership logic of its own and simply reports the refusal.
 *
 * Address, payment and shipment selection is out of scope for MCP, so the checkout request is filled
 * from the customer's own profile plus the configured default payment and shipment method. The
 * customer's name and email are read from the existing `customers` resource rather than supplied by
 * the client, so an assistant cannot check out under a different identity.
 */
class CheckoutTool extends AbstractTool
{
    /**
     * @var string
     */
    protected const TOOL_NAME = 'checkout';

    /**
     * @var string
     */
    protected const PATH_CHECKOUT = '/checkout';

    /**
     * @var string
     */
    protected const PATH_CART = '/carts/%s';

    /**
     * @var string
     */
    protected const PATH_CUSTOMER = '/customers/%s';

    /**
     * @var string
     */
    protected const PATH_CUSTOMER_ADDRESSES = '/customers/%s/addresses';

    /**
     * @var string
     */
    protected const ARGUMENT_CART_ID = 'cartId';

    /**
     * @var string
     */
    protected const RESOURCE_TYPE_CHECKOUT = 'checkout';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ID_CART = 'idCart';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CUSTOMER = 'customer';

    /**
     * @var string
     */
    protected const ATTRIBUTE_BILLING_ADDRESS = 'billingAddress';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SHIPPING_ADDRESS = 'shippingAddress';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PAYMENTS = 'payments';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SHIPMENT = 'shipment';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PAYMENT_METHOD_NAME = 'paymentMethodName';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PAYMENT_PROVIDER_NAME = 'paymentProviderName';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ID_SHIPMENT_METHOD = 'idShipmentMethod';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ORDER_REFERENCE = 'orderReference';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SALUTATION = 'salutation';

    /**
     * @var string
     */
    protected const ATTRIBUTE_FIRST_NAME = 'firstName';

    /**
     * @var string
     */
    protected const ATTRIBUTE_LAST_NAME = 'lastName';

    /**
     * @var string
     */
    protected const ATTRIBUTE_EMAIL = 'email';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ADDRESS1 = 'address1';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ADDRESS2 = 'address2';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ZIP_CODE = 'zipCode';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CITY = 'city';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ISO2_CODE = 'iso2Code';

    /**
     * @var string
     */
    protected const RESULT_KEY_ORDER_REFERENCE = 'orderReference';

    /**
     * @var string
     */
    protected const RESULT_KEY_CART_ID = 'cartId';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CART_ID = 'A cart identifier is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_CUSTOMER = 'The customer profile could not be read.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_NO_ORDER_REFERENCE = 'The order was not confirmed by the shop.';

    public function __construct(
        StorefrontSubRequestInvokerInterface $storefrontSubRequestInvoker,
        protected readonly McpCommerceConfig $mcpCommerceConfig,
    ) {
        parent::__construct($storefrontSubRequestInvoker);
    }

    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    public function getDescription(): string
    {
        return 'Places the order for one of the customer\'s carts and returns the resulting order '
            . 'reference. Uses the customer\'s stored checkout details; the cart must belong to the '
            . 'customer and must not be empty.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return [
            static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_OBJECT,
            static::SCHEMA_KEY_PROPERTIES => [
                static::ARGUMENT_CART_ID => [
                    static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_STRING,
                    static::SCHEMA_KEY_DESCRIPTION => 'The identifier of the cart to order, as returned by the add-to-cart tool.',
                ],
            ],
            static::SCHEMA_KEY_REQUIRED => [static::ARGUMENT_CART_ID],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(McpIdentityTransfer $mcpIdentityTransfer, array $arguments): ToolResult
    {
        $cartId = $this->readStringArgument($arguments, static::ARGUMENT_CART_ID);

        if ($cartId === '') {
            return ToolResult::createError(static::ERROR_MESSAGE_MISSING_CART_ID);
        }

        $identityClaims = $this->createIdentityClaims($mcpIdentityTransfer);
        $customerReference = (string)$mcpIdentityTransfer->getCustomerReference();
        $customerAttributes = $this->fetchCustomerAttributes($customerReference, $identityClaims);

        if ($customerAttributes === []) {
            return ToolResult::createError(static::ERROR_MESSAGE_UNKNOWN_CUSTOMER);
        }

        return $this->placeOrder($cartId, $customerReference, $customerAttributes, $identityClaims);
    }

    /**
     * @param array<string, mixed> $customerAttributes
     * @param array<string, mixed> $identityClaims
     */
    protected function placeOrder(
        string $cartId,
        string $customerReference,
        array $customerAttributes,
        array $identityClaims,
    ): ToolResult {
        // Reading the cart first makes a cart the customer does not own fail as an ownership problem
        // ("Cart not found") rather than as a checkout payload validation problem, which would
        // otherwise mask the real reason. The `carts` resource is what enforces the ownership.
        $cartResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_CART, rawurlencode($cartId)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$cartResult->isSuccessful()) {
            return ToolResult::createError($this->extractErrorMessage($cartResult));
        }

        $address = $this->resolveAddress($customerReference, $customerAttributes, $identityClaims);

        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            static::PATH_CHECKOUT,
            static::METHOD_POST,
            $identityClaims,
            $this->createCheckoutBody($cartId, $customerAttributes, $address),
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return ToolResult::createError($this->extractErrorMessage($storefrontSubRequestResult));
        }

        $checkoutAttributes = $this->extractResourceAttributes($storefrontSubRequestResult);
        $orderReference = $checkoutAttributes[static::ATTRIBUTE_ORDER_REFERENCE] ?? null;

        if (!is_string($orderReference) || $orderReference === '') {
            return ToolResult::createError(static::ERROR_MESSAGE_NO_ORDER_REFERENCE);
        }

        return ToolResult::createSuccess([
            static::RESULT_KEY_ORDER_REFERENCE => $orderReference,
            static::RESULT_KEY_CART_ID => $cartId,
        ]);
    }

    /**
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>
     */
    protected function fetchCustomerAttributes(string $customerReference, array $identityClaims): array
    {
        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_CUSTOMER, rawurlencode($customerReference)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return [];
        }

        return $this->extractResourceAttributes($storefrontSubRequestResult);
    }

    /**
     * Prefers the customer's first stored address and falls back to their profile name, so checkout
     * never depends on address data supplied by the assistant.
     *
     * @param array<string, mixed> $customerAttributes
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>
     */
    protected function resolveAddress(
        string $customerReference,
        array $customerAttributes,
        array $identityClaims,
    ): array {
        $storedAddress = $this->findFirstStoredAddress($customerReference, $identityClaims);

        if ($storedAddress !== []) {
            return $storedAddress;
        }

        return [
            static::ATTRIBUTE_SALUTATION => (string)($customerAttributes[static::ATTRIBUTE_SALUTATION] ?? ''),
            static::ATTRIBUTE_FIRST_NAME => (string)($customerAttributes[static::ATTRIBUTE_FIRST_NAME] ?? ''),
            static::ATTRIBUTE_LAST_NAME => (string)($customerAttributes[static::ATTRIBUTE_LAST_NAME] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>
     */
    protected function findFirstStoredAddress(string $customerReference, array $identityClaims): array
    {
        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_CUSTOMER_ADDRESSES, rawurlencode($customerReference)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return [];
        }

        foreach ($this->extractResourceCollection($storefrontSubRequestResult) as $resource) {
            $attributes = $resource[static::PAYLOAD_KEY_ATTRIBUTES] ?? null;

            if (is_array($attributes) && $attributes !== []) {
                return $this->mapAddress($attributes);
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function mapAddress(array $attributes): array
    {
        $addressKeys = [
            static::ATTRIBUTE_SALUTATION,
            static::ATTRIBUTE_FIRST_NAME,
            static::ATTRIBUTE_LAST_NAME,
            static::ATTRIBUTE_ADDRESS1,
            static::ATTRIBUTE_ADDRESS2,
            static::ATTRIBUTE_ZIP_CODE,
            static::ATTRIBUTE_CITY,
            static::ATTRIBUTE_ISO2_CODE,
        ];

        $address = [];

        foreach ($addressKeys as $addressKey) {
            if (!isset($attributes[$addressKey])) {
                continue;
            }

            $address[$addressKey] = $attributes[$addressKey];
        }

        return $address;
    }

    /**
     * @param array<string, mixed> $customerAttributes
     * @param array<string, mixed> $address
     *
     * @return array<string, mixed>
     */
    protected function createCheckoutBody(string $cartId, array $customerAttributes, array $address): array
    {
        return [
            static::PAYLOAD_KEY_DATA => [
                static::PAYLOAD_KEY_TYPE => static::RESOURCE_TYPE_CHECKOUT,
                static::PAYLOAD_KEY_ATTRIBUTES => [
                    static::ATTRIBUTE_ID_CART => $cartId,
                    static::ATTRIBUTE_CUSTOMER => $this->createCustomerData($customerAttributes),
                    static::ATTRIBUTE_BILLING_ADDRESS => $address,
                    static::ATTRIBUTE_SHIPPING_ADDRESS => $address,
                    static::ATTRIBUTE_PAYMENTS => [
                        [
                            static::ATTRIBUTE_PAYMENT_METHOD_NAME => $this->mcpCommerceConfig->getCheckoutPaymentMethodName(),
                            static::ATTRIBUTE_PAYMENT_PROVIDER_NAME => $this->mcpCommerceConfig->getCheckoutPaymentProviderName(),
                        ],
                    ],
                    static::ATTRIBUTE_SHIPMENT => [
                        static::ATTRIBUTE_ID_SHIPMENT_METHOD => $this->mcpCommerceConfig->getCheckoutIdShipmentMethod(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $customerAttributes
     *
     * @return array<string, mixed>
     */
    protected function createCustomerData(array $customerAttributes): array
    {
        return [
            static::ATTRIBUTE_SALUTATION => (string)($customerAttributes[static::ATTRIBUTE_SALUTATION] ?? ''),
            static::ATTRIBUTE_FIRST_NAME => (string)($customerAttributes[static::ATTRIBUTE_FIRST_NAME] ?? ''),
            static::ATTRIBUTE_LAST_NAME => (string)($customerAttributes[static::ATTRIBUTE_LAST_NAME] ?? ''),
            static::ATTRIBUTE_EMAIL => (string)($customerAttributes[static::ATTRIBUTE_EMAIL] ?? ''),
        ];
    }
}
