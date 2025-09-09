<?php

namespace Go\Client\TenantBehavior;

use Spryker\Client\Kernel\AbstractFactory;

class TenantBehaviorFactory extends AbstractFactory
{
    /**
     * @return string|null
     */
    public function getTenantReference(): ?string
    {
        return $this->getProvidedDependency(TenantBehaviorDependencyProvider::SERVICE_TENANT_REFERENCE);
    }
}
