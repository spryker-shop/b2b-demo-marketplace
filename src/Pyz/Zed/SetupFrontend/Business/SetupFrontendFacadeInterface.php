<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SetupFrontend\Business;

use Generated\Shared\Transfer\SetupFrontendConfigurationTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Zed\SetupFrontend\Business\SetupFrontendFacadeInterface as SprykerSetupFrontendFacadeInterface;

interface SetupFrontendFacadeInterface extends SprykerSetupFrontendFacadeInterface
{
    /**
     * Specification:
     * - Runs Storybook frontend builder.
     * - Uses `SetupFrontendConfigurationTransfer` to configure the build process.
     *
     * @api
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Generated\Shared\Transfer\SetupFrontendConfigurationTransfer $setupFrontendConfigurationTransfer
     *
     * @return bool
     */
    public function buildStorybookFrontend(LoggerInterface $logger, SetupFrontendConfigurationTransfer $setupFrontendConfigurationTransfer): bool;
}
