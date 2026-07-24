<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Orm\Zed\Payment\Persistence\SpyPaymentMethodQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class PaymentExpander
{
    public function addPayment(QuoteTransfer $quoteTransfer, DataSetInterface $dataSet): QuoteTransfer
    {
        $paymentMethodKey = $dataSet[SalesOrderDataSetInterface::COLUMN_PAYMENT_METHOD_KEY];
        $paymentMethodEntity = SpyPaymentMethodQuery::create()
            ->joinWithSpyPaymentProvider()
            ->findOneByPaymentMethodKey($paymentMethodKey);

        if (!$paymentMethodEntity) {
            throw new EntityNotFoundException(sprintf('Payment method with key "%s" is not found.', $paymentMethodKey));
        }

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentSelection($paymentMethodKey)
            ->setPaymentProvider($paymentMethodEntity->getSpyPaymentProvider()->getPaymentProviderKey())
            ->setPaymentMethod($paymentMethodEntity->getName())
            ->setPaymentMethodName($paymentMethodEntity->getName())
            ->setAmount($quoteTransfer->getTotalsOrFail()->getGrandTotal());

        return $quoteTransfer
            ->setPayment($paymentTransfer)
            ->addPayment($paymentTransfer);
    }
}
