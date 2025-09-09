<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Persistence;

use Generated\Shared\Transfer\TenantCollectionTransfer;
use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationCollectionTransfer;
use Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;

interface TenantOnboardingRepositoryInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationCollectionTransfer
     */
    public function getTenantRegistrations(TenantRegistrationCriteriaTransfer $criteriaTransfer): TenantRegistrationCollectionTransfer;

    /**
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer|null
     */
    public function findTenantRegistrationById(int $idTenantRegistration): ?TenantRegistrationTransfer;

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isEmailAvailable(string $email): bool;

    /**
     * @param string $tenantName
     *
     * @return bool
     */
    public function isTenantNameAvailable(string $tenantName): bool;

    /**
     * @param \Generated\Shared\Transfer\TenantCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantCollectionTransfer
     */
    public function getTenants(TenantCriteriaTransfer $criteriaTransfer): TenantCollectionTransfer;

    /**
     * @param int $idTenant
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantById(int $idTenant): ?TenantTransfer;

    /**
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByIdentifier(string $identifier): ?TenantTransfer;

    /**
     * @param string $tenantHost
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByHost(string $tenantHost): ?TenantTransfer;

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isTenantIdentifierAvailable(string $identifier): bool;

    /**
     * @param string $tenantHost
     *
     * @return bool
     */
    public function isTenantHostAvailable(string $tenantHost): bool;
}
