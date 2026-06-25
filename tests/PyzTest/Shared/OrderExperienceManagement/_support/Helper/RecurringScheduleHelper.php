<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Shared\OrderExperienceManagement\Helper;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper as SprykerFeatureRecurringScheduleHelper;

class RecurringScheduleHelper extends SprykerFeatureRecurringScheduleHelper
{
    protected function buildMinimalQuoteData(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        /** @var \SprykerTest\Shared\Customer\Helper\CustomerDataHelper $customerDataHelper */
        $customerDataHelper = $this->getModule('\SprykerTest\Shared\Customer\Helper\CustomerDataHelper');
        $customerTransfer = $customerDataHelper->haveConfirmedCustomer(['locale_name' => 'en_US']);

        /** @var \SprykerTest\Shared\Store\Helper\StoreDataHelper $storeDataHelper */
        $storeDataHelper = $this->getModule('\SprykerTest\Shared\Store\Helper\StoreDataHelper');
        $storeTransfer = $storeDataHelper->haveStore([StoreTransfer::NAME => $recurringScheduleTransfer->getStoreNameOrFail()]);

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentMethod($recurringScheduleTransfer->getPaymentMethodOrFail())
            ->setPaymentProvider('DummyPayment')
            ->setPaymentSelection('dummyPaymentInvoice');

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
            ->setShippingAddress($addressTransfer);

        return json_encode($quoteTransfer->toArray(), JSON_THROW_ON_ERROR);
    }
}
