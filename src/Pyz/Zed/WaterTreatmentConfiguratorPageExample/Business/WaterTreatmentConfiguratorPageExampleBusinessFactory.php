<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business;

use Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\Builder\FrontendBuilder;
use Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\Builder\FrontendBuilderInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method \Pyz\Zed\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig getConfig()
 */
class WaterTreatmentConfiguratorPageExampleBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\Builder\FrontendBuilderInterface
     */
    public function createProductConfiguratorFrontendBuilder(): FrontendBuilderInterface
    {
        return new FrontendBuilder(
            $this->createFilesystem(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    public function createFilesystem(): Filesystem
    {
        return new Filesystem();
    }
}
