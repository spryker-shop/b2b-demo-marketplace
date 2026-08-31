<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SetupFrontend\Business;

use Spryker\Zed\SetupFrontend\Business\Model\Builder\Builder;
use Spryker\Zed\SetupFrontend\Business\Model\Builder\BuilderInterface;
use Spryker\Zed\SetupFrontend\Business\SetupFrontendBusinessFactory as SprykerSetupFrontendBusinessFactory;

/**
 * @method \Pyz\Zed\SetupFrontend\SetupFrontendConfig getConfig()
 */
class SetupFrontendBusinessFactory extends SprykerSetupFrontendBusinessFactory
{
    public function createStorybookBuilder(): BuilderInterface
    {
        return new Builder($this->getConfig()->getStorybookBuildCommand());
    }
}
