<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\MerchantPortalApplication\Communication;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\Kernel\Container\ContainerProxy;
use Spryker\Zed\MerchantPortalApplication\Communication\MerchantPortalApplicationCommunicationFactory as SprykerMerchantPortalApplicationCommunicationFactory;

/**
 * @method \Pyz\Zed\MerchantPortalApplication\MerchantPortalApplicationConfig getConfig()
 */
class MerchantPortalApplicationCommunicationFactory extends SprykerMerchantPortalApplicationCommunicationFactory
{
    public function createServiceContainer(): ContainerInterface
    {
        return new ContainerProxy([
            'logger' => null,
            'debug' => $this->getConfig()->isDebugModeEnabled(),
            'charset' => 'UTF-8',
            'canUseDi' => true,
        ]);
    }
}
