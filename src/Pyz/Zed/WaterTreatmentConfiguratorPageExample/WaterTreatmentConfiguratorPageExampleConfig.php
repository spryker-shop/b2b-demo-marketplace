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
     *
     * The configurator frontend is a generic, data-driven app: in production it loads the options from
     * `./configurator.json` (served from `public/WaterTreatmentConfigurator/`). We reuse the pre-built app
     * shipped with the `spryker-shop/date-time-configurator-page-example` package and feed it our own
     * `configurator.json` (water treatment), so no project-level frontend build is required.
     */
    protected const FRONTEND_ORIGIN_PATH = '../../../../vendor/spryker-shop/date-time-configurator-page-example/src/SprykerShop/Configurator/DateTimeConfiguratorPageExample/Theme/ConfiguratorApplication/dist';

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
