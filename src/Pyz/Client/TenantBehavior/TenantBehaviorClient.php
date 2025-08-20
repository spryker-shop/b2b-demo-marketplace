<?php

namespace Pyz\Client\TenantBehavior;


use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Pyz\Client\TenantBehavior\TenantBehaviorFactory getFactory()
 */
class TenantBehaviorClient extends AbstractClient implements TenantBehaviorClientInterface
{
    /**
     * @return string|null
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->getFactory()->getTenantId();
    }
}
