<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfigurator\Business;

use Psr\Log\LoggerInterface;

interface WaterTreatmentConfiguratorFacadeInterface
{
    /**
     * Specification:
     * - Builds (deploys) the Water Treatment product configurator frontend by mirroring
     *   the built frontend application into the public web root.
     *
     * @api
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return bool
     */
    public function buildProductConfigurationFrontend(LoggerInterface $logger): bool;
}
