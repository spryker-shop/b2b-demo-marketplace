<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

/**
 * @method \Pyz\Zed\TenantAssigner\TenantAssignerConfig getConfig()
 * @method \Pyz\Zed\TenantAssigner\Business\TenantAssignerFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface getRepository()
 */
class TenantAssignerCommunicationFactory extends AbstractCommunicationFactory
{
}
