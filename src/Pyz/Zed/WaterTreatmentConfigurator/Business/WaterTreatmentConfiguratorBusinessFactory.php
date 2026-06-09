<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfigurator\Business;

use Pyz\Zed\WaterTreatmentConfigurator\Business\Builder\FrontendBuilder;
use Pyz\Zed\WaterTreatmentConfigurator\Business\Builder\FrontendBuilderInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method \Pyz\Zed\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig getConfig()
 */
class WaterTreatmentConfiguratorBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\WaterTreatmentConfigurator\Business\Builder\FrontendBuilderInterface
     */
    public function createProductConfiguratorFrontendBuilder(): FrontendBuilderInterface
    {
        return new FrontendBuilder(
            new Filesystem(),
            $this->getConfig(),
        );
    }
}
