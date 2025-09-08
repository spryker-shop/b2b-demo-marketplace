<?php

namespace Go\Client\TenantBehavior;

interface TenantBehaviorClientInterface
{
    /**
     * @return string|null
     */
    public function getCurrentTenantReference(): ?string;
}
