<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationCollectionTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Generated\Shared\Transfer\TenantCollectionTransfer;
use Generated\Shared\Transfer\TenantResponseTransfer;

interface TenantOnboardingFacadeInterface
{
    /**
     * Specification:
     * - Validates tenant registration data
     * - Checks for duplicate email and tenant name
     * - Hashes password according to policy
     * - Persists registration with 'pending' status
     * - Returns success/failure response with errors
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function submitRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationResponseTransfer;

    /**
     * Specification:
     * - Updates registration status to 'approved'
     * - Queues onboarding steps for processing
     * - Returns success/failure response
     *
     * @api
     *
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function acceptRegistration(int $idTenantRegistration): TenantRegistrationResponseTransfer;

    /**
     * Specification:
     * - Updates registration status to 'declined'
     * - Sets decline reason
     * - Sends decline notification email
     * - Returns success/failure response
     *
     * @api
     *
     * @param int $idTenantRegistration
     * @param string $reason
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function declineRegistration(int $idTenantRegistration, string $reason): TenantRegistrationResponseTransfer;

    /**
     * Specification:
     * - Retrieves tenant registrations based on criteria
     * - Supports filtering by status, email, tenant name
     * - Supports pagination
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationCollectionTransfer
     */
    public function getTenantRegistrations(TenantRegistrationCriteriaTransfer $criteriaTransfer): TenantRegistrationCollectionTransfer;

    /**
     * Specification:
     * - Finds tenant registration by ID
     * - Returns null if not found
     *
     * @api
     *
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer|null
     */
    public function findTenantRegistrationById(int $idTenantRegistration): ?TenantRegistrationTransfer;

    /**
     * Specification:
     * - Processes onboarding step for tenant
     * - Executes registered step plugins
     * - Handles retry logic for failed steps
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantOnboardingMessageTransfer $messageTransfer
     *
     * @return void
     */
    public function processOnboardingStep(TenantOnboardingMessageTransfer $messageTransfer): void;

    /**
     * Specification:
     * - Checks if email is not already registered
     * - Returns true if available, false if taken
     *
     * @api
     *
     * @param string $email
     *
     * @return bool
     */
    public function isEmailAvailable(string $email): bool;

    /**
     * Specification:
     * - Checks if tenant name is not already taken
     * - Returns true if available, false if taken
     *
     * @api
     *
     * @param string $tenantName
     *
     * @return bool
     */
    public function isTenantNameAvailable(string $tenantName): bool;

    /**
     * Specification:
     * - Creates a new tenant record
     * - Validates tenant data (identifier, host uniqueness)
     * - Persists tenant to database
     * - Returns created tenant transfer
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function createTenant(TenantTransfer $tenantTransfer): TenantTransfer;

    /**
     * Specification:
     * - Updates existing tenant record
     * - Validates tenant data
     * - Persists changes to database
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function updateTenant(TenantTransfer $tenantTransfer): TenantTransfer;

    /**
     * Specification:
     * - Deletes tenant by ID
     * - Removes tenant record from database
     *
     * @api
     *
     * @param int $idTenant
     *
     * @return void
     */
    public function deleteTenant(int $idTenant): void;

    /**
     * Specification:
     * - Retrieves tenants based on criteria
     * - Supports filtering by identifier, host
     * - Supports pagination
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantCollectionTransfer
     */
    public function getTenants(TenantCriteriaTransfer $criteriaTransfer): TenantCollectionTransfer;

    /**
     * Specification:
     * - Finds tenant by ID
     * - Returns null if not found
     *
     * @api
     *
     * @param int $idTenant
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantById(int $idTenant): ?TenantTransfer;

    /**
     * Specification:
     * - Finds tenant by identifier
     * - Returns null if not found
     *
     * @api
     *
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByIdentifier(string $identifier): ?TenantTransfer;
}