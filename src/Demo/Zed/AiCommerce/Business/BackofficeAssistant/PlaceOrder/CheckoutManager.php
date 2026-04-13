<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\DummyPaymentTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteProcessFlowTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Shared\CheckoutExtension\CheckoutExtensionContextsInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;

class CheckoutManager implements CheckoutManagerInterface
{
    protected const string ADDRESS_TYPE_SHIPPING = 'shipping';

    protected const string ADDRESS_TYPE_BILLING = 'billing';

    protected const string ADDRESS_TYPE_BOTH = 'both';

    protected const string PAYMENT_METHOD_DUMMY_INVOICE = 'dummyPaymentInvoice';

    protected const string DEFAULT_DATE_OF_BIRTH = '1990-01-01';

    protected const string DEFAULT_COUNTRY_CODE = 'DE';

    protected const string COUNTRY_CODE_AT = 'AT';

    protected const string STORE_NAME_AT = 'AT';

    public function __construct(
        protected readonly QuoteFacadeInterface $quoteFacade,
        protected readonly CartFacadeInterface $cartFacade,
        protected readonly CustomerFacadeInterface $customerFacade,
        protected readonly CheckoutFacadeInterface $checkoutFacade,
        protected readonly ShipmentFacadeInterface $shipmentFacade,
        protected readonly PaymentFacadeInterface $paymentFacade,
        protected readonly QuoteCustomerHydratorInterface $quoteCustomerHydrator,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function getCheckoutData(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        $this->quoteCustomerHydrator->drainFlashMessages();

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        $hasItems = $quote->getItems()->count() > 0;

        $result = [
            'idQuote' => $quote->getIdQuote(),
            'itemCount' => $quote->getItems()->count(),
            'customerAddresses' => $this->getCustomerAddresses($quote),
        ];

        if (!$hasItems) {
            $result['shipmentMethods'] = [];
            $result['paymentMethods'] = [];
            $result['note'] = 'Add items first to see available shipment and payment methods.';

            return (string)json_encode($result);
        }

        $this->ensureItemShipments($quote);
        $result['shipmentMethods'] = $this->getShipmentMethods($quote);
        $result['paymentMethods'] = $this->getPaymentMethods($quote);

        return (string)json_encode($result);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setAddress(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $type = (string)($arguments['type'] ?? static::ADDRESS_TYPE_BOTH);

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $addressTransfer = (new AddressTransfer())
            ->setSalutation((string)($arguments['salutation'] ?? ''))
            ->setFirstName((string)($arguments['firstName'] ?? ''))
            ->setLastName((string)($arguments['lastName'] ?? ''))
            ->setAddress1((string)($arguments['address1'] ?? ''))
            ->setAddress2((string)($arguments['address2'] ?? ''))
            ->setCity((string)($arguments['city'] ?? ''))
            ->setZipCode((string)($arguments['zipCode'] ?? ''))
            ->setIso2Code((string)($arguments['iso2Code'] ?? ''))
            ->setCompany((string)($arguments['company'] ?? ''))
            ->setPhone((string)($arguments['phone'] ?? ''))
            ->setEmail((string)($arguments['email'] ?? ''));

        if ($type === static::ADDRESS_TYPE_SHIPPING || $type === static::ADDRESS_TYPE_BOTH) {
            $quote->setShippingAddress($addressTransfer);

            foreach ($quote->getItems() as $item) {
                $shipment = $item->getShipment() ?? new ShipmentTransfer();
                $shipment->setShippingAddress($addressTransfer);
                $item->setShipment($shipment);
            }
        }

        if ($type === static::ADDRESS_TYPE_BILLING || $type === static::ADDRESS_TYPE_BOTH) {
            $quote->setBillingAddress($addressTransfer);
        }

        if ($type === static::ADDRESS_TYPE_BOTH) {
            $quote->setBillingSameAsShipping(true);
        }

        $updateResponse = $this->quoteFacade->updateQuote($quote);

        if (!$updateResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Failed to update %s address', $type)]);
        }

        $formattedAddress = sprintf(
            '%s %s, %s %s, %s %s',
            $addressTransfer->getFirstName(),
            $addressTransfer->getLastName(),
            $addressTransfer->getAddress1(),
            $addressTransfer->getAddress2(),
            $addressTransfer->getZipCode(),
            $addressTransfer->getCity(),
        );

        return (string)json_encode([
            'success' => true,
            'type' => $type,
            'address' => $formattedAddress,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setShipmentMethod(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $idShipmentMethod = (int)($arguments['idShipmentMethod'] ?? 0);
        $shipmentTypeUuid = $arguments['shipmentTypeUuid'] ?? null;

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        if ($idShipmentMethod === 0) {
            return $this->listAvailableShipmentMethods($quote);
        }

        $shipmentTransfer = (new ShipmentTransfer())
            ->setMethod((new ShipmentMethodTransfer())->setIdShipmentMethod($idShipmentMethod))
            ->setShipmentSelection((string)$idShipmentMethod)
            ->setShippingAddress($quote->getShippingAddress());

        if ($shipmentTypeUuid !== null) {
            $shipmentTransfer->setShipmentTypeUuid((string)$shipmentTypeUuid);
        }

        foreach ($quote->getItems() as $item) {
            $item->setShipment($shipmentTransfer);
        }

        $quote->setQuoteProcessFlow(
            (new QuoteProcessFlowTransfer())->setName(CheckoutExtensionContextsInterface::CONTEXT_CHECKOUT),
        );

        $previousPayment = $quote->getPayment();

        $quote = $this->shipmentFacade->expandQuoteWithShipmentGroups($quote);

        if ($previousPayment !== null && ($quote->getPayment() === null || $quote->getPayment()->getPaymentMethod() === null)) {
            $previousPayment->setAmount($quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0);
            $quote->setPayment($previousPayment);
        }

        $updateResponse = $this->quoteFacade->updateQuote($quote);

        if (!$updateResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => 'Failed to update quote after shipment']);
        }

        return (string)json_encode([
            'success' => true,
            'grandTotal' => $quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0,
            'expenseTotal' => $quote->getTotals() ? $quote->getTotals()->getExpenseTotal() : 0,
            'paymentCleared' => $previousPayment !== null && ($quote->getPayment() === null || $quote->getPayment()->getPaymentMethod() === null),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setPayment(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $paymentProvider = (string)($arguments['paymentProvider'] ?? '');
        $paymentMethod = (string)($arguments['paymentMethod'] ?? '');
        $dateOfBirth = (string)($arguments['dateOfBirth'] ?? static::DEFAULT_DATE_OF_BIRTH);

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        if ($paymentProvider === '' && $paymentMethod === '') {
            return $this->listAvailablePaymentMethods($quote);
        }

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentSelection($paymentMethod)
            ->setPaymentProvider($paymentProvider)
            ->setPaymentMethod($paymentMethod)
            ->setAmount($quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0);

        if ($paymentMethod === static::PAYMENT_METHOD_DUMMY_INVOICE) {
            $paymentTransfer->setDummyPaymentInvoice(
                (new DummyPaymentTransfer())->setDateOfBirth($dateOfBirth),
            );
        }

        $quote->setPayment($paymentTransfer);

        $updateResponse = $this->quoteFacade->updateQuote($quote);

        if (!$updateResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => 'Failed to update payment']);
        }

        return (string)json_encode([
            'success' => true,
            'paymentProvider' => $paymentProvider,
            'paymentMethod' => $paymentMethod,
            'amount' => $paymentTransfer->getAmount(),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function placeOrder(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        $this->quoteCustomerHydrator->drainFlashMessages();

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        if ($quote->getItems()->count() === 0) {
            return (string)json_encode(['error' => 'Quote has no items. Add items before placing an order.']);
        }

        $addressTransfer = (new AddressTransfer())
            ->setSalutation((string)($arguments['salutation'] ?? ''))
            ->setFirstName((string)($arguments['firstName'] ?? ''))
            ->setLastName((string)($arguments['lastName'] ?? ''))
            ->setAddress1((string)($arguments['address1'] ?? ''))
            ->setAddress2((string)($arguments['address2'] ?? ''))
            ->setCity((string)($arguments['city'] ?? ''))
            ->setZipCode((string)($arguments['zipCode'] ?? ''))
            ->setIso2Code((string)($arguments['iso2Code'] ?? ''))
            ->setEmail((string)($arguments['email'] ?? $quote->getCustomer()?->getEmail() ?? ''))
            ->setPhone((string)($arguments['phone'] ?? ''))
            ->setCompany((string)($arguments['company'] ?? ''));

        $quote->setShippingAddress($addressTransfer);
        $quote->setBillingAddress($addressTransfer);
        $quote->setBillingSameAsShipping(true);

        $idShipmentMethod = (int)($arguments['idShipmentMethod'] ?? 0);
        $shipmentTransfer = (new ShipmentTransfer())
            ->setMethod((new ShipmentMethodTransfer())->setIdShipmentMethod($idShipmentMethod))
            ->setShipmentSelection((string)$idShipmentMethod)
            ->setShippingAddress($addressTransfer);

        foreach ($quote->getItems() as $item) {
            $item->setShipment($shipmentTransfer);
        }

        $quote = $this->shipmentFacade->expandQuoteWithShipmentGroups($quote);

        $paymentProvider = (string)($arguments['paymentProvider'] ?? '');
        $paymentMethod = (string)($arguments['paymentMethod'] ?? '');
        $dateOfBirth = (string)($arguments['dateOfBirth'] ?? static::DEFAULT_DATE_OF_BIRTH);

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentSelection($paymentMethod)
            ->setPaymentProvider($paymentProvider)
            ->setPaymentMethod($paymentMethod)
            ->setAmount($quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0);

        if ($paymentMethod === static::PAYMENT_METHOD_DUMMY_INVOICE) {
            $paymentTransfer->setDummyPaymentInvoice(
                (new DummyPaymentTransfer())->setDateOfBirth($dateOfBirth),
            );
        }

        $quote->setPayment($paymentTransfer);

        $quote->setQuoteProcessFlow(
            (new QuoteProcessFlowTransfer())->setName(CheckoutExtensionContextsInterface::CONTEXT_CHECKOUT),
        );

        $checkoutResponse = $this->checkoutFacade->placeOrder($quote);

        $this->quoteCustomerHydrator->drainFlashMessages();

        if (!$checkoutResponse->getIsSuccess()) {
            $errors = [];

            foreach ($checkoutResponse->getErrors() as $error) {
                $errors[] = ['code' => $error->getErrorCode(), 'message' => $error->getMessage()];
            }

            return (string)json_encode(['success' => false, 'errors' => $errors]);
        }

        $saveOrder = $checkoutResponse->getSaveOrder();

        $this->quoteFacade->deleteQuote($quote);

        if ($saveOrder === null) {
            return (string)json_encode(['success' => true]);
        }

        return (string)json_encode([
            'success' => true,
            'orderReference' => $saveOrder->getOrderReference(),
            'idSalesOrder' => $saveOrder->getIdSalesOrder(),
        ]);
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quote
     *
     * @return void
     */
    protected function ensureItemShipments(QuoteTransfer $quote): void
    {
        $placeholderAddress = (new AddressTransfer())
            ->setIso2Code($quote->getStore()?->getName() === static::STORE_NAME_AT ? static::COUNTRY_CODE_AT : static::DEFAULT_COUNTRY_CODE);

        foreach ($quote->getItems() as $item) {
            if ($item->getShipment() !== null) {
                continue;
            }

            $item->setShipment(
                (new ShipmentTransfer())->setShippingAddress($placeholderAddress),
            );
        }
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quote
     *
     * @return array<string, mixed>
     */
    protected function getCustomerAddresses(QuoteTransfer $quote): array
    {
        $customer = $quote->getCustomer();

        if ($customer === null) {
            return ['addresses' => [], 'defaultShippingAddressId' => null, 'defaultBillingAddressId' => null];
        }

        $addressesTransfer = $this->customerFacade->getAddresses($customer);

        $addresses = [];

        foreach ($addressesTransfer->getAddresses() as $address) {
            $addresses[] = [
                'idCustomerAddress' => $address->getIdCustomerAddress(),
                'salutation' => $address->getSalutation(),
                'firstName' => $address->getFirstName(),
                'lastName' => $address->getLastName(),
                'address1' => $address->getAddress1(),
                'address2' => $address->getAddress2(),
                'zipCode' => $address->getZipCode(),
                'city' => $address->getCity(),
                'iso2Code' => $address->getIso2Code(),
                'company' => $address->getCompany(),
                'phone' => $address->getPhone(),
                'email' => $address->getEmail(),
            ];
        }

        return [
            'addresses' => $addresses,
            'defaultShippingAddressId' => $customer->getDefaultShippingAddress(),
            'defaultBillingAddressId' => $customer->getDefaultBillingAddress(),
        ];
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quote
     *
     * @return array<array<string, mixed>>
     */
    protected function getShipmentMethods(QuoteTransfer $quote): array
    {
        $methodsCollection = $this->shipmentFacade->getAvailableMethodsByShipment($quote);

        $methods = [];

        foreach ($methodsCollection->getShipmentMethods() as $shipmentMethods) {
            foreach ($shipmentMethods->getMethods() as $method) {
                $methods[] = [
                    'idShipmentMethod' => $method->getIdShipmentMethod(),
                    'name' => $method->getName(),
                    'carrierName' => $method->getCarrierName(),
                    'price' => $method->getStoreCurrencyPrice(),
                ];
            }
        }

        return $methods;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quote
     *
     * @return array<array<string, mixed>>
     */
    protected function getPaymentMethods(QuoteTransfer $quote): array
    {
        $paymentMethods = $this->paymentFacade->getAvailableMethods($quote);

        $methods = [];

        foreach ($paymentMethods->getMethods() as $method) {
            $methods[] = [
                'idPaymentMethod' => $method->getIdPaymentMethod(),
                'name' => $method->getName(),
                'methodKey' => $method->getPaymentMethodKey(),
                'providerName' => $method->getPaymentProvider() !== null
                    ? $method->getPaymentProvider()->getName()
                    : $method->getGroupName(),
            ];
        }

        return $methods;
    }

    protected function listAvailableShipmentMethods(QuoteTransfer $quote): string
    {
        $methodsCollection = $this->shipmentFacade->getAvailableMethodsByShipment($quote);

        $methods = [];

        foreach ($methodsCollection->getShipmentMethods() as $shipmentMethods) {
            foreach ($shipmentMethods->getMethods() as $method) {
                $methods[] = [
                    'idShipmentMethod' => $method->getIdShipmentMethod(),
                    'name' => $method->getName(),
                    'carrierName' => $method->getCarrierName(),
                    'price' => $method->getStoreCurrencyPrice(),
                ];
            }
        }

        return (string)json_encode(['methods' => $methods]);
    }

    protected function listAvailablePaymentMethods(QuoteTransfer $quote): string
    {
        $paymentMethods = $this->paymentFacade->getAvailableMethods($quote);

        $methods = [];

        foreach ($paymentMethods->getMethods() as $method) {
            $methods[] = [
                'idPaymentMethod' => $method->getIdPaymentMethod(),
                'name' => $method->getName(),
                'methodKey' => $method->getPaymentMethodKey(),
            ];
        }

        return (string)json_encode(['methods' => $methods]);
    }
}
