<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class SalesOrderWriterStep implements DataImportStepInterface
{
    public function __construct(
        protected CheckoutFacadeInterface $checkoutFacade,
        protected QuoteBuilder $quoteBuilder,
        protected ItemsExpander $itemsExpander,
        protected ShipmentExpander $shipmentExpander,
        protected PaymentExpander $paymentExpander,
        protected OmsEventTrigger $omsEventTrigger,
    ) {
    }

    public function execute(DataSetInterface $dataSet): void
    {
        if ($this->orderExists($dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE])) {
            return;
        }

        $quoteTransfer = $this->quoteBuilder->buildQuote($dataSet);
        $quoteTransfer = $this->itemsExpander->addItems($quoteTransfer, $dataSet);
        $quoteTransfer = $this->shipmentExpander->addShipment($quoteTransfer, $dataSet);
        $quoteTransfer = $this->paymentExpander->addPayment($quoteTransfer, $dataSet);

        $checkoutResponseTransfer = $this->checkoutFacade->placeOrder($quoteTransfer);

        if (!$checkoutResponseTransfer->getIsSuccess()) {
            throw new DataImportException(sprintf(
                'Order "%s" could not be placed: %s',
                $dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE],
                $this->buildCheckoutErrorMessage($checkoutResponseTransfer),
            ));
        }

        $this->omsEventTrigger->triggerOmsEvents($checkoutResponseTransfer, $dataSet);
    }

    protected function orderExists(string $orderReference): bool
    {
        return SpySalesOrderQuery::create()
            ->filterByOrderReference($orderReference)
            ->exists();
    }

    protected function buildCheckoutErrorMessage(CheckoutResponseTransfer $checkoutResponseTransfer): string
    {
        $errorMessages = [];

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $errorMessages[] = $checkoutErrorTransfer->getMessage();
        }

        return implode('; ', $errorMessages) ?: 'unknown error';
    }
}
