<?php

namespace Go\Client\TenantOnboarding;

use Generated\Shared\Transfer\TenantStorageTransfer;

interface TenantOnboardingClientInterface
{
    public function findTenantByHost(string $id): ?TenantStorageTransfer;
    public function findTenantByIdentifier(string $id): ?TenantStorageTransfer;
}
