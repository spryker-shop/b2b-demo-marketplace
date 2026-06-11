<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class WaterTreatmentConfiguratorPageExampleConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    protected const FRONTEND_TARGET_PATH = '/public/WaterTreatmentConfigurator/dist';

    /**
     * @var string
     */
    protected const FRONTEND_ORIGIN_PATH = '../../Yves/WaterTreatmentConfiguratorPageExample/Theme/ConfiguratorApplication/dist';

    /**
     * Path to the built configurator frontend directory.
     *
     * @api
     *
     * @return string
     */
    public function getFrontendOriginPath(): string
    {
        return sprintf('%s/%s', __DIR__, static::FRONTEND_ORIGIN_PATH);
    }

    /**
     * Path to the configurator frontend web root directory.
     *
     * @api
     *
     * @return string
     */
    public function getFrontendTargetPath(): string
    {
        return sprintf('%s/%s', APPLICATION_ROOT_DIR, static::FRONTEND_TARGET_PATH);
    }
}
