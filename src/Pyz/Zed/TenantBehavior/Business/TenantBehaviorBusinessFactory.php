<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantBehavior\Business;

use Pyz\Zed\TenantBehavior\TenantBehaviorDependencyProvider;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

class TenantBehaviorBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return string|null
     */
    public function getTenantId(): ?string
    {
        return $this->getProvidedDependency(TenantBehaviorDependencyProvider::SERVICE_TENANT_ID);
    }
}
