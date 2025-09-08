<?php

namespace Go\Client\TenantOnboarding;

use Generated\Shared\Transfer\TenantStorageTransfer;

interface TenantOnboardingClientInterface
{
    public function findTenantByID(string $id): ?TenantStorageTransfer;
}
