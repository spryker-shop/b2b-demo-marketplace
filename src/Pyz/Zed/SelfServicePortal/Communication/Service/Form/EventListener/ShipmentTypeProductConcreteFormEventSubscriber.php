<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SelfServicePortal\Communication\Service\Form\EventListener;

use Generated\Shared\Transfer\ProductOfferShipmentTypeConditionsTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ShipmentTypeConditionsTransfer;
use Generated\Shared\Transfer\ShipmentTypeCriteriaTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductOfferShipmentType\Business\ProductOfferShipmentTypeFacadeInterface;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\EventListener\ShipmentTypeProductConcreteFormEventSubscriber as SprykerShipmentTypeProductConcreteFormEventSubscriber;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\ShipmentTypeProductConcreteForm;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;
use Symfony\Component\Form\FormEvent;

class ShipmentTypeProductConcreteFormEventSubscriber extends SprykerShipmentTypeProductConcreteFormEventSubscriber
{
    public function __construct(
        ProductOfferShipmentTypeFacadeInterface $productOfferShipmentTypeFacade,
        ShipmentTypeFacadeInterface $shipmentTypeFacade,
        ProductFacadeInterface $productFacade,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
    ) {
        parent::__construct($productOfferShipmentTypeFacade, $shipmentTypeFacade, $productFacade);
    }

    public function validateShipmentTypes(FormEvent $event): void
    {
        $formData = $event->getForm()->getData();
        if (!is_array($formData)) {
            return;
        }

        $idProductConcrete = $formData[ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE] ?? null;
        if (!$idProductConcrete) {
            return;
        }

        $currentShipmentTypeIds = $this->getCurrentProductShipmentTypeIds((int)$idProductConcrete);
        if (!$currentShipmentTypeIds) {
            return;
        }

        $shipmentTypeCollectionTransfer = $this->shipmentTypeFacade->getShipmentTypeCollection(
            (new ShipmentTypeCriteriaTransfer())->setShipmentTypeConditions(
                (new ShipmentTypeConditionsTransfer())->setShipmentTypeIds($currentShipmentTypeIds),
            ),
        );

        $shipmentTypesIndexedById = $this->indexShipmentTypesById($shipmentTypeCollectionTransfer);
        $newShipmentTypeIds = $this->extractSubmittedShipmentTypeIds($formData);
        $removedShipmentTypeIds = array_diff(array_keys($shipmentTypesIndexedById), $newShipmentTypeIds);

        if (!$removedShipmentTypeIds) {
            return;
        }

        $concreteSkus = $this->productFacade->getProductConcreteSkusByConcreteIds([(int)$idProductConcrete]);
        $productOfferShipmentTypeCollectionTransfer = $this->productOfferShipmentTypeFacade->getProductOfferShipmentTypeCollection(
            (new ProductOfferShipmentTypeCriteriaTransfer())->setProductOfferShipmentTypeConditions(
                (new ProductOfferShipmentTypeConditionsTransfer())
                    ->setProductConcreteSkus(array_keys($concreteSkus))
                    ->setShipmentTypeIds($removedShipmentTypeIds),
            ),
        );

        if ($productOfferShipmentTypeCollectionTransfer->getProductOfferShipmentTypes()->count() === 0) {
            return;
        }

        $conflictingShipmentTypeIds = $this->extractConflictingShipmentTypeIds(
            $productOfferShipmentTypeCollectionTransfer->getProductOfferShipmentTypes()->getArrayCopy(),
        );

        $this->addShipmentTypeFormErrors($event->getForm(), $conflictingShipmentTypeIds, $shipmentTypesIndexedById);
    }

    /**
     * @param int $idProductConcrete
     *
     * @return list<int>
     */
    protected function getCurrentProductShipmentTypeIds(int $idProductConcrete): array
    {
        $shipmentTypeIdsGroupedByIdProductConcrete = $this->selfServicePortalRepository
            ->getShipmentTypeIdsGroupedByIdProductConcrete([$idProductConcrete]);

        return $shipmentTypeIdsGroupedByIdProductConcrete[$idProductConcrete] ?? [];
    }
}
