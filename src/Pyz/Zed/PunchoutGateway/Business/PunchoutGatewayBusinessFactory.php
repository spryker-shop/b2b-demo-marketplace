<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PunchoutGateway\Business;

use Pyz\Zed\PunchoutGateway\Business\DemoConnection\PunchoutDemoConnectionCreator;
use Pyz\Zed\PunchoutGateway\Business\DemoConnection\PunchoutDemoConnectionCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory as SprykerEcoPunchoutGatewayBusinessFactory;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayBusinessFactory extends SprykerEcoPunchoutGatewayBusinessFactory
{
    public function createPunchoutDemoConnectionCreator(): PunchoutDemoConnectionCreatorInterface
    {
        return new PunchoutDemoConnectionCreator(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getStoreFacade(),
            $this->getCustomerFacade(),
        );
    }
}
