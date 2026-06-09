<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfigurator\Business;

use Psr\Log\LoggerInterface;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\WaterTreatmentConfigurator\Business\WaterTreatmentConfiguratorBusinessFactory getFactory()
 */
class WaterTreatmentConfiguratorFacade extends AbstractFacade implements WaterTreatmentConfiguratorFacadeInterface
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
