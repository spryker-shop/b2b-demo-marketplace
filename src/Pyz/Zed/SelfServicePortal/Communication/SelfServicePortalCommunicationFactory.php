<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SelfServicePortal\Communication;

use Pyz\Zed\SelfServicePortal\Communication\Service\Form\EventListener\ShipmentTypeProductConcreteFormEventSubscriber;
use SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory as SprykerFeatureSelfServicePortalCommunicationFactory;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface getEntityManager()
 * @method \Pyz\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface getRepository()
 */
class SelfServicePortalCommunicationFactory extends SprykerFeatureSelfServicePortalCommunicationFactory
{
    public function createShipmentTypeProductConcreteFormEventSubscriber(): ShipmentTypeProductConcreteFormEventSubscriber
    {
        return new ShipmentTypeProductConcreteFormEventSubscriber(
            $this->getProductOfferShipmentTypeFacade(),
            $this->getShipmentTypeFacade(),
            $this->getProductFacade(),
            $this->getRepository(),
        );
    }
}
