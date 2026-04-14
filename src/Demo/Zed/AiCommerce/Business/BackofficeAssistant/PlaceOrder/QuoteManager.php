<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Service\Container\Container;
use Spryker\Shared\Kernel\Container\GlobalContainer;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;

class QuoteManager implements QuoteManagerInterface
{
    protected const string DEFAULT_STORE_NAME = 'DE';

    protected const string DEFAULT_CURRENCY_CODE = 'EUR';

    protected const string PRICE_MODE_NET = 'NET';

    protected const string PRICE_MODE_GROSS = 'GROSS';

    protected const string CONTAINER_KEY_STORE = 'store';

    public function __construct(
        protected readonly QuoteFacadeInterface $quoteFacade,
        protected readonly CartFacadeInterface $cartFacade,
        protected readonly CustomerFacadeInterface $customerFacade,
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
    public function createQuote(array $arguments): string
    {
        $customerReference = (string)($arguments['customerReference'] ?? '');
        $storeName = (string)($arguments['storeName'] ?? static::DEFAULT_STORE_NAME);
        $currencyCode = (string)($arguments['currencyCode'] ?? static::DEFAULT_CURRENCY_CODE);
        $priceMode = (string)($arguments['priceMode'] ?? static::PRICE_MODE_GROSS);

        // Set store context so cart/checkout plugins can resolve store-specific config
        $container = new Container();
        $container->set(static::CONTAINER_KEY_STORE, $storeName);
        GlobalContainer::setContainer($container);

        $customerResponse = $this->customerFacade->findCustomerByReference($customerReference);

        if (!$customerResponse->getHasCustomer()) {
            return (string)json_encode(['error' => sprintf('Customer with reference "%s" not found.', $customerReference)]);
        }

        $customerTransfer = $customerResponse->getCustomerTransfer();

        if ($customerTransfer === null) {
            return (string)json_encode(['error' => sprintf('Customer with reference "%s" not found.', $customerReference)]);
        }

        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer($customerTransfer)
            ->setStore((new StoreTransfer())->setName($storeName))
            ->setCurrency((new CurrencyTransfer())->setCode($currencyCode))
            ->setPriceMode($priceMode === static::PRICE_MODE_NET ? PriceConfig::PRICE_MODE_NET : PriceConfig::PRICE_MODE_GROSS);

        $quoteResponse = $this->quoteFacade->createQuote($quoteTransfer);

        if (!$quoteResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($quoteResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to create quote', 'details' => $errors]);
        }

        $quote = $quoteResponse->getQuoteTransfer();

        if ($quote === null) {
            return (string)json_encode(['error' => 'Failed to retrieve created quote.']);
        }

        return (string)json_encode([
            'idQuote' => $quote->getIdQuote(),
            'customerName' => sprintf('%s %s', $customerTransfer->getFirstName(), $customerTransfer->getLastName()),
            'storeName' => $storeName,
            'currencyCode' => $currencyCode,
            'priceMode' => $priceMode,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function deleteQuote(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);

        $quoteTransfer = (new QuoteTransfer())->setIdQuote($idQuote);
        $response = $this->quoteFacade->deleteQuote($quoteTransfer);

        return (string)json_encode(['success' => $response->getIsSuccessful()]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function getQuoteSummary(array $arguments): string
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

        // Reload items from DB — findQuoteById returns a minimal quote without computed state
        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        $this->quoteCustomerHydrator->drainFlashMessages();

        $items = [];

        foreach ($quote->getItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'sumPrice' => $item->getSumPrice(),
                'groupKey' => $item->getGroupKey(),
                'merchantReference' => $item->getMerchantReference(),
            ];
        }

        $totals = null;

        if ($quote->getTotals()) {
            $totals = [
                'subtotal' => $quote->getTotals()->getSubtotal(),
                'grandTotal' => $quote->getTotals()->getGrandTotal(),
                'taxTotal' => $quote->getTotals()->getTaxTotal() ? $quote->getTotals()->getTaxTotal()->getAmount() : 0,
                'expenseTotal' => $quote->getTotals()->getExpenseTotal(),
                'discountTotal' => $quote->getTotals()->getDiscountTotal(),
            ];
        }

        $customer = null;

        if ($quote->getCustomer()) {
            $customer = [
                'customerReference' => $quote->getCustomer()->getCustomerReference(),
                'firstName' => $quote->getCustomer()->getFirstName(),
                'lastName' => $quote->getCustomer()->getLastName(),
                'email' => $quote->getCustomer()->getEmail(),
            ];
        }

        $shippingAddress = $this->formatAddress($quote->getShippingAddress());
        $billingAddress = $this->formatAddress($quote->getBillingAddress());
        $billingSameAsShipping = $quote->getBillingSameAsShipping() ?? false;

        $shipment = null;

        foreach ($quote->getItems() as $item) {
            if ($item->getShipment() !== null && $item->getShipment()->getMethod() !== null) {
                $method = $item->getShipment()->getMethod();
                $shipment = [
                    'idShipmentMethod' => $method->getIdShipmentMethod(),
                    'name' => $method->getName(),
                    'carrierName' => $method->getCarrierName(),
                    'price' => $method->getStoreCurrencyPrice(),
                ];

                break;
            }
        }

        $payment = null;

        if ($quote->getPayment()) {
            $payment = [
                'provider' => $quote->getPayment()->getPaymentProvider(),
                'method' => $quote->getPayment()->getPaymentMethod(),
                'amount' => $quote->getPayment()->getAmount(),
            ];
        }

        $voucherCodes = [];

        foreach ($quote->getVoucherDiscounts() as $discount) {
            $voucherCodes[] = $discount->getVoucherCode();
        }

        $expenses = [];

        foreach ($quote->getExpenses() as $expense) {
            $expenses[] = [
                'type' => $expense->getType(),
                'name' => $expense->getName(),
                'sumPrice' => $expense->getSumPrice(),
            ];
        }

        return (string)json_encode([
            'idQuote' => $quote->getIdQuote(),
            'customer' => $customer,
            'itemCount' => count($items),
            'items' => $items,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'billingSameAsShipping' => $billingSameAsShipping,
            'shipment' => $shipment,
            'payment' => $payment,
            'totals' => $totals,
            'voucherCodes' => $voucherCodes,
            'expenses' => $expenses,
        ]);
    }

    /**
     * @param mixed $address
     *
     * @return array<string, mixed>|null
     */
    protected function formatAddress(mixed $address): ?array
    {
        if ($address === null || $address->getAddress1() === null) {
            return null;
        }

        return [
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
        ];
    }
}
