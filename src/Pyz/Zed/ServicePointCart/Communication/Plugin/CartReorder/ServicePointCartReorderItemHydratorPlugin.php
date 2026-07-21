<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ServicePointCart\Communication\Plugin\CartReorder;

use Generated\Shared\Transfer\CartReorderTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ServicePointTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Orm\Zed\ProductOfferServicePoint\Persistence\SpyProductOfferServiceQuery;
use Orm\Zed\ProductOfferShipmentType\Persistence\SpyProductOfferShipmentTypeQuery;
use Orm\Zed\ServicePoint\Persistence\SpyServicePointQuery;
use Orm\Zed\ServicePoint\Persistence\SpyServiceQuery;
use Orm\Zed\ShipmentType\Persistence\SpyShipmentTypeQuery;
use Spryker\Shared\ShipmentType\ShipmentTypeConfig;
use Spryker\Zed\CartReorderExtension\Dependency\Plugin\CartReorderItemHydratorPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Restores the service context (shipment type and service point) on reorder items whose product offer
 * is sold via a non-delivery shipment type (e.g. in-center-service).
 *
 * The sales order snapshot (`spy_sales_order_item_service_point`) stores only the service point name and key,
 * so without this hydrator amendment/reorder carts lose `ItemTransfer.shipmentType` and `ItemTransfer.servicePoint`.
 * Checkout then assigns the default `delivery` type to such items and the Click&Collect replacement fails with
 * `click_and_collect_example.error.item_replacement_not_found`, because service-only offers cannot be delivered.
 *
 * @method \Spryker\Zed\ServicePointCart\ServicePointCartConfig getConfig()
 * @method \Spryker\Zed\ServicePointCart\Business\ServicePointCartFacadeInterface getFacade()
 */
class ServicePointCartReorderItemHydratorPlugin extends AbstractPlugin implements CartReorderItemHydratorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expects reorder items to be hydrated with `productOfferReference` (e.g. by `ProductOfferCartReorderItemHydratorPlugin`).
     * - Sets `ItemTransfer.shipmentType` for reorder items whose product offer has a non-delivery shipment type.
     * - Sets `ItemTransfer.servicePoint` for those items when the product offer provides a service at a service point.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartReorderTransfer $cartReorderTransfer
     *
     * @return \Generated\Shared\Transfer\CartReorderTransfer
     */
    public function hydrate(CartReorderTransfer $cartReorderTransfer): CartReorderTransfer
    {
        foreach ($cartReorderTransfer->getReorderItems() as $reorderItemTransfer) {
            if (!$reorderItemTransfer->getProductOfferReference()) {
                continue;
            }

            $this->expandItemWithServiceDetails($reorderItemTransfer);
        }

        return $cartReorderTransfer;
    }

    protected function expandItemWithServiceDetails(ItemTransfer $itemTransfer): void
    {
        $productOfferEntity = SpyProductOfferQuery::create()
            ->findOneByProductOfferReference($itemTransfer->getProductOfferReferenceOrFail());

        if (!$productOfferEntity) {
            return;
        }

        $productOfferShipmentTypeEntity = SpyProductOfferShipmentTypeQuery::create()
            ->findOneByFkProductOffer($productOfferEntity->getIdProductOffer());

        if (!$productOfferShipmentTypeEntity) {
            return;
        }

        $shipmentTypeEntity = SpyShipmentTypeQuery::create()
            ->findPk($productOfferShipmentTypeEntity->getFkShipmentType());

        if (!$shipmentTypeEntity || $shipmentTypeEntity->getKey() === ShipmentTypeConfig::SHIPMENT_TYPE_DELIVERY) {
            return;
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
            return;
        }

        $serviceEntity = SpyServiceQuery::create()->findPk($productOfferServiceEntity->getFkService());
        $servicePointEntity = $serviceEntity
            ? SpyServicePointQuery::create()->findPk($serviceEntity->getFkServicePoint())
            : null;

        if (!$servicePointEntity) {
            return;
        }

        $itemTransfer->setServicePoint(
            (new ServicePointTransfer())
                ->setIdServicePoint($servicePointEntity->getIdServicePoint())
                ->setUuid($servicePointEntity->getUuid())
                ->setKey($servicePointEntity->getKey())
                ->setName($servicePointEntity->getName()),
        );
    }
}
