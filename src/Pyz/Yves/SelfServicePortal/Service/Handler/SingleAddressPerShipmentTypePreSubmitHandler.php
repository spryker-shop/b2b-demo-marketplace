<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\SelfServicePortal\Service\Handler;

use Generated\Shared\Transfer\ItemTransfer;
use SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Yves\SelfServicePortal\Service\Form\SingleAddressPerShipmentTypeAddressStepForm;
use SprykerFeature\Yves\SelfServicePortal\Service\Handler\SingleAddressPerShipmentTypePreSubmitHandler as SprykerFeatureSingleAddressPerShipmentTypePreSubmitHandlerInterface;
use Symfony\Component\Form\FormEvent;

class SingleAddressPerShipmentTypePreSubmitHandler extends SprykerFeatureSingleAddressPerShipmentTypePreSubmitHandlerInterface
{
    public function handlePreSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        $form = $event->getForm();

        if (!$this->shouldProcessEvent($data)) {
            return;
        }

        $currentShipmentTypeKey = $data[static::FIELD_SHIPMENT_TYPE][static::FIELD_SHIPMENT_TYPE_KEY] ?? SelfServicePortalConfig::SHIPMENT_TYPE_DELIVERY;

        $checkoutMultiShippingAddressesForm = $form->getParent();
        if (!$checkoutMultiShippingAddressesForm) {
            return;
        }

        foreach ($checkoutMultiShippingAddressesForm->all() as $checkoutAddressForm) {
            if ($checkoutAddressForm === $form) {
                continue;
            }

            /** @var \Generated\Shared\Transfer\ItemTransfer $itemTransfer */
            $itemTransfer = $checkoutAddressForm->getData();

            if (!$this->isSameShipmentType($itemTransfer, $currentShipmentTypeKey)) {
                continue;
            }

            if (!$itemTransfer->getIsSingleAddressPerShipmentType()) {
                continue;
            }

            $data = $this->copyAddressFromSiblingForm($data, $itemTransfer);
            $event->setData($data);

            return;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return bool
     */
    protected function shouldProcessEvent(array $data): bool
    {
        if (isset($data[SingleAddressPerShipmentTypeAddressStepForm::FIELD_IS_SINGLE_ADDRESS_PER_SHIPMENT_TYPE])) {
            return false;
        }

        if (!isset($data[static::FIELD_SHIPPING_ADDRESS])) {
            return false;
        }

        $shipmentTypeKey = $data[static::FIELD_SHIPMENT_TYPE][static::FIELD_SHIPMENT_TYPE_KEY] ?? SelfServicePortalConfig::SHIPMENT_TYPE_DELIVERY;

        return $this->addressFormChecker->isApplicableShipmentType($shipmentTypeKey);
    }

    protected function isSameShipmentType(ItemTransfer $itemTransfer, string $currentShipmentTypeKey): bool
    {
        return $itemTransfer->getShipmentType()?->getKey() ?? $currentShipmentTypeKey === SelfServicePortalConfig::SHIPMENT_TYPE_DELIVERY;
    }
}
