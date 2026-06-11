<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business;

use Psr\Log\LoggerInterface;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\WaterTreatmentConfiguratorPageExampleBusinessFactory getFactory()
 */
class WaterTreatmentConfiguratorPageExampleFacade extends AbstractFacade implements WaterTreatmentConfiguratorPageExampleFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return bool
     */
    public function buildProductConfigurationFrontend(LoggerInterface $logger): bool
    {
        return $this->getFactory()
            ->createProductConfiguratorFrontendBuilder()
            ->build($logger);
    }
}
