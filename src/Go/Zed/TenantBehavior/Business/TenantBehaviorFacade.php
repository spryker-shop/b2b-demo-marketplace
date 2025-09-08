<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantBehavior\Business;

use Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Go\Zed\TenantBehavior\Business\TenantBehaviorBusinessFactory getFactory()
 */
class TenantBehaviorFacade extends AbstractFacade implements TenantBehaviorFacadeInterface
{
    static protected ?string $TENANT_REFERENCE = null;

    /**
     * @return string|null
     */
    public function getCurrentTenantReference(): ?string
    {
        if (static::$TENANT_REFERENCE === null) {
            static::$TENANT_REFERENCE = $this->getFactory()->getTenantReference();
        }

        return static::$TENANT_REFERENCE;
    }

    public function setCurrentTenantReference(?string $tenantReference): void
    {
        static::$TENANT_REFERENCE = $tenantReference;
    }
}
