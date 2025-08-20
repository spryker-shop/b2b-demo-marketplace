<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantBehavior\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Pyz\Zed\TenantBehavior\Business\TenantBehaviorBusinessFactory getFactory()
 */
class TenantBehaviorFacade extends AbstractFacade implements TenantBehaviorFacadeInterface
{
    static ?string $ID_TENANT = null;

    /**
     * @return string|null
     */
    public function getCurrentTenantId(): ?string
    {
        if (static::$ID_TENANT === null) {
            static::$ID_TENANT = $this->getFactory()->getTenantId();
        }

        return static::$ID_TENANT;
    }

    public function setCurrentTenantId(?string $idTenant): void
    {
        static::$ID_TENANT = $idTenant;
    }
}
