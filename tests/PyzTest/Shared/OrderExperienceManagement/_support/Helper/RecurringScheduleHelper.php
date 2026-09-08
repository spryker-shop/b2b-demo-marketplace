<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Shared\OrderExperienceManagement\Helper;

use Generated\Shared\DataBuilder\ItemBuilder;
use Generated\Shared\DataBuilder\ShipmentBuilder;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Orm\Zed\Payment\Persistence\SpyPaymentMethodQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper as SprykerFeatureRecurringScheduleHelper;

class RecurringScheduleHelper extends SprykerFeatureRecurringScheduleHelper
{
    protected const string DEFAULT_PAYMENT_PROVIDER = 'DummyPayment';

    protected function buildMinimalQuoteData(RecurringScheduleTransfer $recurringScheduleTransfer, array $quoteDataOverrides = []): string
    {
        /** @var \SprykerTest\Shared\Customer\Helper\CustomerDataHelper $customerDataHelper */
        $customerDataHelper = $this->getModule('\SprykerTest\Shared\Customer\Helper\CustomerDataHelper');
        $customerTransfer = $customerDataHelper->haveConfirmedCustomer(['locale_name' => 'en_US']);

        /** @var \SprykerTest\Shared\Store\Helper\StoreDataHelper $storeDataHelper */
        $storeDataHelper = $this->getModule('\SprykerTest\Shared\Store\Helper\StoreDataHelper');
        $storeTransfer = $storeDataHelper->haveStore([StoreTransfer::NAME => $recurringScheduleTransfer->getStoreNameOrFail()]);

        $paymentMethodKey = $recurringScheduleTransfer->getPaymentMethodOrFail();

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentMethod($paymentMethodKey)
            ->setPaymentProvider($this->resolvePaymentProviderKey($paymentMethodKey))
            ->setPaymentSelection($paymentMethodKey);

        $totalsTransfer = (new TotalsTransfer())
            ->setGrandTotal(0)
            ->setSubtotal(0);

        $currencyTransfer = (new CurrencyTransfer())
            ->setCode($recurringScheduleTransfer->getCurrencyIsoCodeOrFail());

        $addressTransfer = $this->buildMockAddressTransfer();

        $quoteTransfer = (new QuoteTransfer())
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setStore($storeTransfer)
            ->setCurrency($currencyTransfer)
            ->setPriceMode($recurringScheduleTransfer->getPriceModeOrFail())
            ->setPayment($paymentTransfer)
            ->setTotals($totalsTransfer)
            ->setBillingAddress($addressTransfer)
            ->setShippingAddress($addressTransfer)
            ->fromArray($quoteDataOverrides, true);

        return json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Resolves the payment provider of the given payment method from the database, so the schedule can be
     * placed as an order in both the marketplace (DummyMarketplacePayment) and the B2B-only (DummyPayment) setup.
     */
    protected function resolvePaymentProviderKey(string $paymentMethodKey): string
    {
        $paymentMethodEntity = SpyPaymentMethodQuery::create()
            ->filterByPaymentMethodKey($paymentMethodKey)
            ->findOne();

        if ($paymentMethodEntity === null) {
            return static::DEFAULT_PAYMENT_PROVIDER;
        }

        return $paymentMethodEntity->getSpyPaymentProvider()->getPaymentProviderKey();
    }

    /**
     * Same as the parent, but keeps the merchant/offer references of the schedule item, so a packaging unit
     * item can be placed as a marketplace order.
     */
    protected function resolveAmountSalesUnitItemData(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        int $idProductMeasurementSalesUnit,
        mixed $amount,
    ): string {
        $sku = $recurringScheduleItemTransfer->getSkuOrFail();

        $productConcreteEntity = SpyProductQuery::create()->findOneBySku($sku);

        /** @var \SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper $shipmentMethodDataHelper */
        $shipmentMethodDataHelper = $this->getModule('\SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper');
        $shipmentMethodTransfer = $shipmentMethodDataHelper->haveShipmentMethod();

        $amountSalesUnitTransfer = (new ProductMeasurementSalesUnitTransfer())
            ->setIdProductMeasurementSalesUnit($idProductMeasurementSalesUnit);

        $itemTransfer = (new ItemBuilder([
            ItemTransfer::SKU => $sku,
            ItemTransfer::ID => $productConcreteEntity?->getIdProduct(),
            ItemTransfer::ID_PRODUCT_ABSTRACT => $productConcreteEntity?->getFkProductAbstract(),
            ItemTransfer::GROUP_KEY => $recurringScheduleItemTransfer->getGroupKey() ?? $sku,
            ItemTransfer::QUANTITY => $recurringScheduleItemTransfer->getQuantityOrFail(),
            ItemTransfer::UNIT_GROSS_PRICE => $recurringScheduleItemTransfer->getReferenceGrossPrice() ?? 0,
            ItemTransfer::UNIT_NET_PRICE => $recurringScheduleItemTransfer->getReferenceNetPrice() ?? 0,
            ItemTransfer::PRODUCT_OFFER_REFERENCE => $recurringScheduleItemTransfer->getProductOfferReference(),
            ItemTransfer::MERCHANT_REFERENCE => $recurringScheduleItemTransfer->getMerchantReference(),
        ]))->withShipment(
            (new ShipmentBuilder())
                ->withMethod($shipmentMethodTransfer->toArray())
                ->withShippingAddress($this->buildMockAddressTransfer()->toArray()),
        )->build()
            ->setAmount((string)$amount)
            ->setAmountSalesUnit($amountSalesUnitTransfer)
            ->setQuantitySalesUnit($amountSalesUnitTransfer);

        return json_encode($itemTransfer->toArray(), JSON_THROW_ON_ERROR);
    }
}
