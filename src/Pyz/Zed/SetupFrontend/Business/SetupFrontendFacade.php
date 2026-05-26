<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SetupFrontend\Business;

use Generated\Shared\Transfer\SetupFrontendConfigurationTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Zed\SetupFrontend\Business\SetupFrontendFacade as SprykerSetupFrontendFacade;

/**
 * @method \Pyz\Zed\SetupFrontend\Business\SetupFrontendBusinessFactory getFactory()
 */
class SetupFrontendFacade extends SprykerSetupFrontendFacade implements SetupFrontendFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Generated\Shared\Transfer\SetupFrontendConfigurationTransfer|null $setupFrontendConfigurationTransfer
     *
     * @return bool
     */
    public function buildStorybookFrontend(LoggerInterface $logger, ?SetupFrontendConfigurationTransfer $setupFrontendConfigurationTransfer = null): bool
    {
        if ($setupFrontendConfigurationTransfer === null) {
            $setupFrontendConfigurationTransfer = new SetupFrontendConfigurationTransfer();
        }

        return $this->getFactory()->createStorybookBuilder()->build($logger, $setupFrontendConfigurationTransfer);
    }
}
