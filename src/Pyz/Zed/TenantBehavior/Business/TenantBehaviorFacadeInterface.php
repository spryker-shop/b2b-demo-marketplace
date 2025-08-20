<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantBehavior\Business;

interface TenantBehaviorFacadeInterface
{
    /**
     * @return string|null
     */
    public function getCurrentTenantId(): ?string;

    public function setCurrentTenantId(?string $idTenant): void;
}
