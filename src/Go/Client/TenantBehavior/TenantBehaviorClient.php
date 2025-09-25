<?php

namespace Go\Client\TenantBehavior;


use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Go\Client\TenantBehavior\TenantBehaviorFactory getFactory()
 */
class TenantBehaviorClient extends AbstractClient implements TenantBehaviorClientInterface
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
