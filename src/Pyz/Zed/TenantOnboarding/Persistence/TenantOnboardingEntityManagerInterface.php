<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Persistence;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Generated\Shared\Transfer\TenantResponseTransfer;

interface TenantOnboardingEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer
     */
    public function createTenantRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationTransfer;

    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer
     */
    public function updateTenantRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationTransfer;

    /**
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function createTenant(TenantTransfer $tenantTransfer): TenantTransfer;

    /**
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function updateTenant(TenantTransfer $tenantTransfer): TenantTransfer;

    /**
     * @param int $idTenant
     *
     * @return void
     */
    public function deleteTenant(int $idTenant): void;
}