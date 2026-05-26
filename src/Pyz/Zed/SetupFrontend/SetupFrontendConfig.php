<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 * 
 * @disclaimer This class is a prototype implementation.
 * It will be moved to the core module spryker/setup-frontend after the prototype confirmation.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SetupFrontend;

use Spryker\Zed\SetupFrontend\SetupFrontendConfig as SprykerSetupFrontendConfig;

class SetupFrontendConfig extends SprykerSetupFrontendConfig
{
    /**
     * @api
     *
     * @return string
     */
    public function getProjectInstallCommand(): string
    {
        return 'npm ci --prefer-offline --legacy-peer-deps';
    }

    /**
     * @api
     *
     * @return string
     */
    public function getStorybookBuildCommand(): string
    {
        return 'npm run storybook:build';
    }
}
