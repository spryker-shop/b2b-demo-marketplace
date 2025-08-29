<?php

namespace Pyz\Client\TenantBehavior;

use Spryker\Client\Kernel\AbstractFactory;

class TenantBehaviorFactory extends AbstractFactory
{
    /**
     * @return string|null
     */
    public function getTenantId(): ?string
    {
        return $this->getProvidedDependency(TenantBehaviorDependencyProvider::SERVICE_TENANT_ID);
    }
}
