<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 * 
 * @disclaimer This class is a prototype implementation.
 * It will be moved to the core module spryker/setup-frontend after the prototype confirmation.
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
     * @param \Generated\Shared\Transfer\SetupFrontendConfigurationTransfer|null $setupFrontendConfigurationTransfer
     *
     * @return bool
     */
    public function buildStorybookFrontend(LoggerInterface $logger, ?SetupFrontendConfigurationTransfer $setupFrontendConfigurationTransfer = null): bool;
}
