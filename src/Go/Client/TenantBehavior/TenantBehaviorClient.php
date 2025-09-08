<?php

namespace Go\Client\TenantBehavior;


use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Go\Client\TenantBehavior\TenantBehaviorFactory getFactory()
 */
class TenantBehaviorClient extends AbstractClient implements TenantBehaviorClientInterface
{
    /**
     * @return string|null
     */
    public function getCurrentTenantReference(): ?string
    {
        return $this->getFactory()->getTenantReference();
    }
}
