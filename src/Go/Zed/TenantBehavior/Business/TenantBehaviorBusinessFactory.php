<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantBehavior\Business;

use Go\Zed\TenantBehavior\TenantBehaviorDependencyProvider;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

class TenantBehaviorBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return string|null
     */
    public function getTenantReference(): ?string
    {
        return $this->getProvidedDependency(TenantBehaviorDependencyProvider::SERVICE_TENANT_REFERENCE);
    }
}
