<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ServicePointTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Orm\Zed\ProductOffer\Persistence\SpyProductOffer;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Orm\Zed\ProductOfferServicePoint\Persistence\SpyProductOfferServiceQuery;
use Orm\Zed\ProductOfferShipmentType\Persistence\SpyProductOfferShipmentTypeQuery;
use Orm\Zed\ServicePoint\Persistence\SpyServicePointQuery;
use Orm\Zed\ServicePoint\Persistence\SpyServiceQuery;
use Orm\Zed\ShipmentType\Persistence\SpyShipmentTypeQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Shared\ShipmentType\ShipmentTypeConfig;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class ItemsExpander
{
    protected const ITEMS_SEPARATOR = ';';

    protected const ITEM_PARTS_SEPARATOR = ':';

    public function __construct(protected CartFacadeInterface $cartFacade)
    {
    }

    public function addItems(QuoteTransfer $quoteTransfer, DataSetInterface $dataSet): QuoteTransfer
    {
        $cartChangeTransfer = (new CartChangeTransfer())->setQuote($quoteTransfer);

        foreach ($this->parseItems($dataSet) as $itemTransfer) {
            $cartChangeTransfer->addItem($itemTransfer);
        }

        $quoteResponseTransfer = $this->cartFacade->addToCart($cartChangeTransfer);

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            $errorMessages = [];

            foreach ($quoteResponseTransfer->getErrors() as $quoteErrorTransfer) {
                $errorMessages[] = $quoteErrorTransfer->getMessage();
            }

            throw new DataImportException(sprintf(
                'Items for order "%s" could not be added to cart: %s',
                $dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE],
                implode('; ', $errorMessages) ?: 'unknown error - check that all SKUs exist, are active, and have prices and stock',
            ));
        }

        return $quoteResponseTransfer->getQuoteTransferOrFail();
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function parseItems(DataSetInterface $dataSet): array
    {
        $itemTransfers = [];

        foreach (explode(static::ITEMS_SEPARATOR, $dataSet[SalesOrderDataSetInterface::COLUMN_ITEMS]) as $itemDefinition) {
            $itemDefinition = trim($itemDefinition);

            if ($itemDefinition === '') {
                continue;
            }

            $itemParts = explode(static::ITEM_PARTS_SEPARATOR, $itemDefinition);

            $itemTransfer = (new ItemTransfer())
                ->setSku($itemParts[0])
                ->setQuantity((int)($itemParts[1] ?? 1));

            if (!empty($itemParts[2])) {
                $itemTransfer = $this->expandItemWithProductOffer($itemTransfer, $itemParts[2]);
            }

            $itemTransfers[] = $itemTransfer;
        }

        if (!$itemTransfers) {
            throw new DataImportException(sprintf(
                'Order "%s" has no items. Provide the "%s" column as "sku:quantity[:product_offer_reference]" separated by "%s".',
                $dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE],
                SalesOrderDataSetInterface::COLUMN_ITEMS,
                static::ITEMS_SEPARATOR,
            ));
        }

        return $itemTransfers;
    }

    protected function expandItemWithProductOffer(ItemTransfer $itemTransfer, string $productOfferReference): ItemTransfer
    {
        $productOfferEntity = SpyProductOfferQuery::create()
            ->findOneByProductOfferReference($productOfferReference);

        if (!$productOfferEntity) {
            throw new EntityNotFoundException(sprintf('Product offer with reference "%s" is not found.', $productOfferReference));
        }

        if ($productOfferEntity->getConcreteSku() !== $itemTransfer->getSku()) {
            throw new EntityNotFoundException(sprintf(
                'Product offer "%s" belongs to SKU "%s", not "%s".',
                $productOfferReference,
                $productOfferEntity->getConcreteSku(),
                $itemTransfer->getSku(),
            ));
        }

        $itemTransfer
            ->setProductOfferReference($productOfferReference)
            ->setMerchantReference($productOfferEntity->getMerchantReference());

        return $this->expandItemWithServiceDetails($itemTransfer, $productOfferEntity);
    }

    /**
     * Items sold via a non-delivery shipment type (e.g. in-center-service) require the shipment type
     * and the service point on the item transfer to pass checkout preconditions and to be persisted.
     */
    protected function expandItemWithServiceDetails(ItemTransfer $itemTransfer, SpyProductOffer $productOfferEntity): ItemTransfer
    {
        $productOfferShipmentTypeEntity = SpyProductOfferShipmentTypeQuery::create()
            ->findOneByFkProductOffer($productOfferEntity->getIdProductOffer());

        if (!$productOfferShipmentTypeEntity) {
            return $itemTransfer;
        }

        $shipmentTypeEntity = SpyShipmentTypeQuery::create()
            ->findPk($productOfferShipmentTypeEntity->getFkShipmentType());

        if (!$shipmentTypeEntity || $shipmentTypeEntity->getKey() === ShipmentTypeConfig::SHIPMENT_TYPE_DELIVERY) {
            return $itemTransfer;
        }

        $itemTransfer->setShipmentType(
            (new ShipmentTypeTransfer())
                ->setIdShipmentType($shipmentTypeEntity->getIdShipmentType())
                ->setUuid($shipmentTypeEntity->getUuid())
                ->setKey($shipmentTypeEntity->getKey())
                ->setName($shipmentTypeEntity->getName()),
        );

        $productOfferServiceEntity = SpyProductOfferServiceQuery::create()
            ->findOneByFkProductOffer($productOfferEntity->getIdProductOffer());

        if (!$productOfferServiceEntity) {
            return $itemTransfer;
        }

        $serviceEntity = SpyServiceQuery::create()->findPk($productOfferServiceEntity->getFkService());
        $servicePointEntity = $serviceEntity
            ? SpyServicePointQuery::create()->findPk($serviceEntity->getFkServicePoint())
            : null;

        if (!$servicePointEntity) {
            return $itemTransfer;
        }

        return $itemTransfer->setServicePoint(
            (new ServicePointTransfer())
                ->setIdServicePoint($servicePointEntity->getIdServicePoint())
                ->setUuid($servicePointEntity->getUuid())
                ->setKey($servicePointEntity->getKey())
                ->setName($servicePointEntity->getName()),
        );
    }
}
