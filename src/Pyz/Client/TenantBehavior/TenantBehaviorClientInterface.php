<?php

namespace Pyz\Client\TenantBehavior;

interface TenantBehaviorClientInterface
{
    /**
     * @return string|null
     */
    public function getCurrentTenantId(): ?string;
}
