<?php

namespace Go\Client\TenantOnboarding;

use Generated\Shared\Transfer\TenantStorageTransfer;
use Spryker\Client\Kernel\AbstractClient;

class TenantOnboardingClient extends AbstractClient implements TenantOnboardingClientInterface
{
    public function findTenantByID(string $id): ?TenantStorageTransfer
    {
        $tenantStorageReader = new \Go\Client\TenantOnboarding\Reader\TenantStorageReader(
            \Spryker\Client\Kernel\Locator::getInstance()->synchronization()->service(),
            \Spryker\Client\Kernel\Locator::getInstance()->storage()->client()
        );

        return $tenantStorageReader->findTenantByID($id);
    }
}
