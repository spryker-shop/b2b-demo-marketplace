<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business\Builder;

use ArrayObject;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CountryCriteriaTransfer;
use Generated\Shared\Transfer\CountryTransfer;
use Generated\Shared\Transfer\CurrencyCriteriaTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\StoreCriteriaTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use InvalidArgumentException;
use Orm\Zed\Customer\Persistence\SpyCustomer;
use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Orm\Zed\Product\Persistence\SpyProductLocalizedAttributesQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Country\Business\CountryFacade;
use Spryker\Zed\Currency\Business\CurrencyFacade;
use Spryker\Zed\Locale\Business\LocaleFacade;
use Spryker\Zed\Store\Business\StoreFacade;

class OrderTransferBuilder
{
    /**
     * @var array<LocaleTransfer>
     */
    protected array $locales;

    /**
     * @var array<CurrencyTransfer>
     */
    protected array $currencies;

    /**
     * @var array<StoreTransfer>
     */
    protected array $stores;

    /**
     * @var array<CountryTransfer>
     */
    protected array $countries;

    public function __construct()
    {
        $this->locales = (new LocaleFacade())->getLocaleCollection(new LocaleCriteriaTransfer());
        $this->currencies = (new CurrencyFacade())->getCurrencyCollection(new CurrencyCriteriaTransfer())->getCurrencies()->getArrayCopy();

        $this->countries = [];
        $countries = (new CountryFacade())->getCountryCollection(new CountryCriteriaTransfer())->getCountries()->getArrayCopy();
        /** @var CountryTransfer $country */
        foreach ($countries as $country) {
            $this->countries[$country->getIso2Code()] = $country;
        }

        $stores = (new StoreFacade())->getStoreCollection(new StoreCriteriaTransfer());
        $this->stores = [];
        /** @var StoreTransfer $storeTransfer */
        foreach ($stores->getStores() as $storeTransfer) {
            $this->stores[$storeTransfer->getName()] = $storeTransfer;
        }
    }

    public function createQuoteTransferFromArray(array $data): QuoteTransfer
    {
        /** @var SpyCustomer $customer */
        $customer = SpyCustomerQuery::create()->filterByEmail($data['customer']['email'], Criteria::LIKE)->findOne();
        if (!$customer) {
            throw new InvalidArgumentException('Customer with email ' . $data['customer']['email'] . ' not found.');
        }
        if (!array_key_exists($data['customer']['billingAddress']['countryCode'] ?? '', $this->countries)) {
            throw new InvalidArgumentException('Billing country with code ' . $data['customer']['billingAddress']['countryCode'] . ' not found.');
        }
        if (!array_key_exists($data['customer']['shippingAddress']['countryCode'] ?? '', $this->countries)) {
            throw new InvalidArgumentException('Shipping country with code ' . $data['customer']['billingAddress']['countryCode'] . ' not found.');
        }
        if (!array_key_exists($data['cart']['currencyCode'] ?? '', $this->currencies)) {
            throw new InvalidArgumentException('Currency with code ' . $data['cart']['currencyCode'] . ' not found.');
        }
        if (!array_key_exists($data['cart']['storeName'] ?? '', $this->stores)) {
            throw new InvalidArgumentException('Store with name ' . $data['cart']['storeName'] . ' not found.');
        }
        if (!in_array($data['cart']['priceMode'] ?? '', ['GROSS_MODE', 'NET_MODE'], true)) {
            throw new InvalidArgumentException('Price mode must be either GROSS_MODE or NET_MODE');
        }
        if (!array_key_exists($data['cart']['localeName'] ?? '', $this->locales)) {
            throw new InvalidArgumentException('Locale with name ' . $data['cart']['localeName'] . ' not found.');
        }

        $isSubTotalProvided = isset($data['cart']['subTotal']) && (int)$data['cart']['subTotal'] > 0;
        $isGrandTotalProvided = isset($data['cart']['grandTotal']) && (int)$data['cart']['grandTotal'] > 0;
        $grandTotal = $isGrandTotalProvided ? (int)$data['cart']['grandTotal'] : 0;
        $subTotal = $isSubTotalProvided ? (int)$data['cart']['subTotal'] : 0;
        $discountTotal = isset($data['cart']['discountTotal']) ? (int)$data['cart']['discountTotal'] : 0;
        $orderReference = !empty(trim($data['cart']['orderReference'] ?? '')) ? trim($data['cart']['orderReference']) : null;

        if ($orderReference) {
            $order = SpySalesOrderQuery::create()->findOneByOrderReference($orderReference);
            if ($order) {
                throw new InvalidArgumentException('Order with reference ' . $orderReference . ' already exists.');
            }
        }

        $shipmentTransfer = (new ShipmentTransfer())
            ->setMethod((new ShipmentMethodTransfer())
                ->setName('Standard'))
//            ->setRequestedDeliveryDate(date('Y-m-d H:i:s'))
            ->setShippingAddress((new AddressTransfer())
                ->setIso2Code($data['customer']['billingAddress']['countryCode'])
                ->setFirstName($data['customer']['billingAddress']['firstName'])
                ->setLastName($data['customer']['billingAddress']['lastName'])
                ->setAddress1($data['customer']['billingAddress']['address1'])
                ->setAddress2($data['customer']['billingAddress']['address2'] ?? '')
                ->setCity($data['customer']['billingAddress']['city'])
                ->setZipCode($data['customer']['billingAddress']['zipCode'])
                ->setPhone($data['customer']['billingAddress']['phone'] ?? ''));

        return (new QuoteTransfer())
            ->setOrderReference($orderReference)
            ->setExpenses(new ArrayObject())
            ->setItems(new ArrayObject(
                array_map(function ($item) use ($shipmentTransfer, &$grandTotal, &$subTotal, $isGrandTotalProvided, $isSubTotalProvided, $data) {
                            $product = SpyProductQuery::create()->findOneBySku($item['concreteSku']);
                    if (!$product) {
                        throw new InvalidArgumentException('Product with SKU ' . $item['concreteSku'] . ' not found.');
                    }
                            $localisedProduct = SpyProductLocalizedAttributesQuery::create()
                                ->filterByFkLocale($this->locales[$data['cart']['localeName']]->getIdLocale())
                                ->findOneByFkProduct($product->getIdProduct());

                    if (!$localisedProduct) {
                        throw new InvalidArgumentException('Localization for ' . $data['cart']['localeName'] . ' for product with SKU ' . $item['concreteSku'] . ' not found.');
                    }

                    if ($item['quantity'] < 1) {
                        throw new InvalidArgumentException('Quantity must be at least 1 for SKU ' . $item['concreteSku']);
                    }
                    if ($item['unitPrice'] < 1) {
                        throw new InvalidArgumentException('Unit price must be at least 1 (cent) for SKU ' . $item['concreteSku']);
                    }

                            $itemTransfer = (new ItemTransfer())
                                ->setName($localisedProduct->getName())
                                ->setQuantity($item['quantity'])
                                ->setSku($item['concreteSku'])
                                ->setUnitPrice($item['unitPrice'] ?? 0)
                                ->setShipment($shipmentTransfer)
                                ->setGroupKey('GROUP-' . $item['concreteSku'])
                                ->setSumGrossPrice($item['unitPrice']);

                    if (!$isGrandTotalProvided) {
                        $grandTotal += $itemTransfer->getUnitPrice() * $itemTransfer->getQuantity();
                    }
                    if (!$isSubTotalProvided) {
                        $subTotal += $itemTransfer->getUnitPrice() * $itemTransfer->getQuantity();
                    }

                            return $itemTransfer;
                },
                $data['items'] ?? []),
            ))
            ->setTotals((new TotalsTransfer())
                ->setGrandTotal($grandTotal)
                ->setPriceToPay($grandTotal)
                ->setSubtotal($subTotal)
                ->setRemunerationTotal(0)
                ->setCanceledTotal(0)
                ->setRefundTotal(0)
                ->setExpenseTotal(0)
                ->setDiscountTotal($discountTotal))
//                ->setNetTotal($grandTotal)
//                ->setShipmentTotal(0)

            ->setBillingAddress((new AddressTransfer())
                ->setIso2Code($data['customer']['billingAddress']['countryCode'])
                ->setFirstName($data['customer']['billingAddress']['firstName'])
                ->setLastName($data['customer']['billingAddress']['lastName'])
                ->setAddress1($data['customer']['billingAddress']['address1'])
                ->setAddress2($data['customer']['billingAddress']['address2'] ?? '')
                ->setCity($data['customer']['billingAddress']['city'])
                ->setZipCode($data['customer']['billingAddress']['zipCode'])
                ->setPhone($data['customer']['billingAddress']['phone'] ?? ''))
            ->setCustomer((new CustomerTransfer())->fromArray($customer->toArray(), true))
           // ->setShippingAddress($shipmentTransfer->getShippingAddress())
            ->setPriceMode($data['cart']['priceMode'])
            ->setStore((new StoreTransfer())->setName($data['cart']['storeName']))
            ->setCurrency((new CurrencyTransfer())->setCode($data['cart']['currencyCode']))
            ->setPayment((new PaymentTransfer())->setPaymentSelection('dummyMarketplacePaymentInvoice'));
    }
}
