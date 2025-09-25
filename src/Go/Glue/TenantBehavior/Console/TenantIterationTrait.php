<?php

namespace Go\Glue\TenantBehavior\Console;

use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Generated\Shared\Transfer\TenantTransfer;

trait TenantIterationTrait
{
    /**
     * Iterate all tenants and run the given callback after switching the tenant context.
     *
     * @param callable(TenantTransfer):void $callback
     */
    protected function forEachTenant(callable $callback): void
    {
        $locator = (new \Spryker\Zed\Kernel\Container())->getLocator();

        $tenantBehaviorFacade = $locator->tenantBehavior()->client();
        $tenantOnboardingFacade = $locator->tenantOnboarding()->facade();

        $tenants = $tenantOnboardingFacade
            ->getTenants(new TenantCriteriaTransfer())
            ->getTenants();

        foreach ($tenants as $tenant) {
            $tenantBehaviorFacade->setCurrentTenantReference($tenant->getIdentifier());
            $callback($tenant);
        }
    }
}
