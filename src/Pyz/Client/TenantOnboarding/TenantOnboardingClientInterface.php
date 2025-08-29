<?php

namespace Pyz\Client\TenantOnboarding;

use Generated\Shared\Transfer\TenantStorageTransfer;

interface TenantOnboardingClientInterface
{
    public function findTenantByID(string $id): ?TenantStorageTransfer;
}
