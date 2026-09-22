<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\RecurringSchedule;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Pyz\Zed\DataImport\Business\Model\SalesOrder\ItemsExpander;
use Pyz\Zed\DataImport\Business\Model\SalesOrder\PaymentExpander;
use Pyz\Zed\DataImport\Business\Model\SalesOrder\QuoteBuilder;
use Pyz\Zed\DataImport\Business\Model\SalesOrder\SalesOrderDataSetInterface;
use Pyz\Zed\DataImport\Business\Model\SalesOrder\ShipmentExpander;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

/**
 * Seeds recurring schedules by placing a real order with recurring order settings on the quote.
 *
 * There is no facade method to create a schedule: the only writer is reached through
 * RecurringOrdersCheckoutPostSavePlugin, which fires when a checkout succeeds and the quote carries
 * `recurringOrderSettings`. This step therefore mirrors the sales-order importer, reusing its
 * collaborators unchanged, and only adds the settings before placing the order.
 *
 * @see \SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Checkout\RecurringOrdersCheckoutPostSavePlugin
 * @see \Pyz\Zed\DataImport\Business\Model\SalesOrder\SalesOrderWriterStep
 */
class RecurringScheduleWriterStep implements DataImportStepInterface
{
    protected const string ERROR_MESSAGE_SEPARATOR = '; ';

    protected const string PAYMENT_METHOD_KEY_SEPARATOR = '", "';

    protected const string DEFAULT_ERROR_MESSAGE = 'unknown error';

    public function __construct(
        protected CheckoutFacadeInterface $checkoutFacade,
        protected QuoteBuilder $quoteBuilder,
        protected ItemsExpander $itemsExpander,
        protected ShipmentExpander $shipmentExpander,
        protected PaymentExpander $paymentExpander,
        protected RecurringOrderSettingsBuilder $recurringOrderSettingsBuilder,
        protected ScheduleStateSeeder $scheduleStateSeeder,
    ) {
    }

    public function execute(DataSetInterface $dataSet): void
    {
        $orderReference = $dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE];

        if ($this->orderExists($orderReference)) {
            return;
        }

        $quoteTransfer = $this->quoteBuilder->buildQuote($dataSet);
        $quoteTransfer = $this->itemsExpander->addItems($quoteTransfer, $dataSet);
        $quoteTransfer = $this->shipmentExpander->addShipment($quoteTransfer, $dataSet);
        $quoteTransfer = $this->paymentExpander->addPayment($quoteTransfer, $dataSet);
        $quoteTransfer->setRecurringOrderSettings(
            $this->recurringOrderSettingsBuilder->buildRecurringOrderSettings($dataSet),
        );

        $checkoutResponseTransfer = $this->checkoutFacade->placeOrder($quoteTransfer);

        if (!$checkoutResponseTransfer->getIsSuccess()) {
            throw new DataImportException(sprintf(
                'Recurring schedule source order "%s" could not be placed: %s',
                $orderReference,
                $this->buildCheckoutErrorMessage($checkoutResponseTransfer),
            ));
        }

        $this->seedScheduleState($dataSet, $orderReference);
    }

    protected function seedScheduleState(DataSetInterface $dataSet, string $orderReference): void
    {
        $idSalesOrder = $this->findIdSalesOrder($orderReference);

        if ($idSalesOrder === null) {
            throw new DataImportException(sprintf('Order "%s" was placed but cannot be found.', $orderReference));
        }

        $recurringScheduleEntity = $this->scheduleStateSeeder->findScheduleBySourceOrder($idSalesOrder);

        if ($recurringScheduleEntity === null) {
            throw new DataImportException(sprintf(
                'No recurring schedule was created for order "%s". The quote was likely not eligible: '
                . 'the payment method must be one of "%s" and the cadence type must be supported.',
                $orderReference,
                implode(static::PAYMENT_METHOD_KEY_SEPARATOR, SharedOrderExperienceManagementConfig::DEFAULT_INVOICE_PAYMENT_METHOD_KEYS),
            ));
        }

        $this->scheduleStateSeeder->clearSourceOrderReference($recurringScheduleEntity);

        $nextTriggerDateExpression = trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_NEXT_TRIGGER_DATE]);

        if ($nextTriggerDateExpression !== '') {
            $this->scheduleStateSeeder->applyNextTriggerDate($recurringScheduleEntity, $nextTriggerDateExpression);
        }

        if (trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_TARGET_STATUS]) !== SharedOrderExperienceManagementConfig::STATUS_FAILED) {
            return;
        }

        $this->scheduleStateSeeder->applyFailedState(
            $recurringScheduleEntity,
            trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_FAILURE_SKU]),
        );
    }

    protected function orderExists(string $orderReference): bool
    {
        return SpySalesOrderQuery::create()
            ->filterByOrderReference($orderReference)
            ->exists();
    }

    protected function findIdSalesOrder(string $orderReference): ?int
    {
        return SpySalesOrderQuery::create()
            ->filterByOrderReference($orderReference)
            ->findOne()
            ?->getIdSalesOrder();
    }

    protected function buildCheckoutErrorMessage(CheckoutResponseTransfer $checkoutResponseTransfer): string
    {
        $errorMessages = [];

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $errorMessages[] = $checkoutErrorTransfer->getMessage();
        }

        return implode(static::ERROR_MESSAGE_SEPARATOR, $errorMessages) ?: static::DEFAULT_ERROR_MESSAGE;
    }
}
