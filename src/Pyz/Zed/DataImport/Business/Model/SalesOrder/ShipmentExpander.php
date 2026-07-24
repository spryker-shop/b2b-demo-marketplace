<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Orm\Zed\Shipment\Persistence\SpyShipmentMethodQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Shared\Shipment\ShipmentConfig;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;

class ShipmentExpander
{
    public function __construct(
        protected ShipmentFacadeInterface $shipmentFacade,
        protected CalculationFacadeInterface $calculationFacade,
    ) {
    }

    public function addShipment(QuoteTransfer $quoteTransfer, DataSetInterface $dataSet): QuoteTransfer
    {
        $defaultShipmentTransfer = $this->createShipmentByMethodKey(
            $dataSet[SalesOrderDataSetInterface::COLUMN_SHIPMENT_METHOD_KEY],
            $quoteTransfer,
        );

        $usedShipmentTransfers = [];
        $serviceShipmentTransfers = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $shipmentTypeTransfer = $itemTransfer->getShipmentType();

            if ($shipmentTypeTransfer === null) {
                $itemTransfer->setShipment($defaultShipmentTransfer);
                $usedShipmentTransfers['default'] = $defaultShipmentTransfer;

                continue;
            }

            $shipmentGroupKey = sprintf(
                '%s:%s',
                $shipmentTypeTransfer->getKeyOrFail(),
                $itemTransfer->getServicePoint()?->getKey() ?? '',
            );

            if (!isset($serviceShipmentTransfers[$shipmentGroupKey])) {
                $serviceShipmentTransfers[$shipmentGroupKey] = $this->createShipmentForShipmentType($itemTransfer, $quoteTransfer);
            }

            $itemTransfer->setShipment($serviceShipmentTransfers[$shipmentGroupKey]);
            $usedShipmentTransfers[$shipmentGroupKey] = $serviceShipmentTransfers[$shipmentGroupKey];
        }

        foreach ($usedShipmentTransfers as $shipmentTransfer) {
            $quoteTransfer->addExpense($this->createShipmentExpense($shipmentTransfer, $quoteTransfer->getPriceMode()));
        }

        return $this->calculationFacade->recalculateQuote($quoteTransfer);
    }

    protected function createShipmentByMethodKey(string $shipmentMethodKey, QuoteTransfer $quoteTransfer): ShipmentTransfer
    {
        $shipmentMethodTransfer = $this->shipmentFacade->findShipmentMethodByKey($shipmentMethodKey);

        if (!$shipmentMethodTransfer) {
            throw new EntityNotFoundException(sprintf('Shipment method with key "%s" is not found.', $shipmentMethodKey));
        }

        return $this->createShipment($shipmentMethodTransfer, $quoteTransfer);
    }

    /**
     * Creates a shipment for a non-delivery item (e.g. in-center-service): resolves the shipment
     * method linked to the item's shipment type and marks the shipment with the shipment type UUID
     * so `ShipmentTypeCheckoutPreConditionPlugin` and `ShipmentTypeCheckoutDoSaveOrderPlugin` can process it.
     *
     * @throws \Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException
     */
    protected function createShipmentForShipmentType(ItemTransfer $itemTransfer, QuoteTransfer $quoteTransfer): ShipmentTransfer
    {
        $shipmentTypeTransfer = $itemTransfer->getShipmentTypeOrFail();

        $shipmentMethodEntity = SpyShipmentMethodQuery::create()
            ->filterByFkShipmentType($shipmentTypeTransfer->getIdShipmentTypeOrFail())
            ->filterByIsActive(true)
            ->findOne();

        if (!$shipmentMethodEntity) {
            throw new EntityNotFoundException(sprintf(
                'No active shipment method is found for shipment type "%s".',
                $shipmentTypeTransfer->getKeyOrFail(),
            ));
        }

        $shipmentMethodTransfer = $this->shipmentFacade->findShipmentMethodByKey($shipmentMethodEntity->getShipmentMethodKey());

        if (!$shipmentMethodTransfer) {
            throw new EntityNotFoundException(sprintf(
                'Shipment method with key "%s" is not found.',
                $shipmentMethodEntity->getShipmentMethodKey(),
            ));
        }

        $shipmentTransfer = $this->createShipment($shipmentMethodTransfer, $quoteTransfer);
        $shipmentTransfer->setShipmentTypeUuid($shipmentTypeTransfer->getUuidOrFail());
        $shipmentTransfer->getMethodOrFail()->setShipmentType((new ShipmentTypeTransfer())->fromArray($shipmentTypeTransfer->toArray()));

        return $shipmentTransfer;
    }

    protected function createShipment(ShipmentMethodTransfer $shipmentMethodTransfer, QuoteTransfer $quoteTransfer): ShipmentTransfer
    {
        $shipmentMethodTransfer = $this->shipmentFacade->findAvailableMethodById(
            $shipmentMethodTransfer->getIdShipmentMethodOrFail(),
            $quoteTransfer,
        ) ?? $shipmentMethodTransfer;

        return (new ShipmentTransfer())
            ->setMethod($shipmentMethodTransfer)
            ->setShipmentSelection((string)$shipmentMethodTransfer->getIdShipmentMethod())
            ->setShippingAddress($quoteTransfer->getShippingAddress());
    }

    protected function createShipmentExpense(ShipmentTransfer $shipmentTransfer, ?string $priceMode): ExpenseTransfer
    {
        $shipmentMethodTransfer = $shipmentTransfer->getMethodOrFail();
        $price = $shipmentMethodTransfer->getStoreCurrencyPrice() ?? 0;

        $expenseTransfer = (new ExpenseTransfer())
            ->setType(ShipmentConfig::SHIPMENT_EXPENSE_TYPE)
            ->setName($shipmentMethodTransfer->getName())
            ->setShipment($shipmentTransfer)
            ->setQuantity(1);

        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            return $expenseTransfer->setUnitNetPrice($price)->setUnitGrossPrice(0);
        }

        return $expenseTransfer->setUnitGrossPrice($price)->setUnitNetPrice(0);
    }
}
