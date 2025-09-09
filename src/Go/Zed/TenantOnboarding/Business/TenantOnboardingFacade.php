<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business;

use Generated\Shared\Transfer\TenantCollectionTransfer;
use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantRegistrationCollectionTransfer;
use Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingBusinessFactory getFactory()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface getEntityManager()
 */
class TenantOnboardingFacade extends AbstractFacade implements TenantOnboardingFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function submitRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationResponseTransfer
    {
        return $this->getFactory()
            ->createRegistrationSubmitter()
            ->submit($tenantRegistrationTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function acceptRegistration(int $idTenantRegistration): TenantRegistrationResponseTransfer
    {
        return $this->getFactory()
            ->createRegistrationAccepter()
            ->accept($idTenantRegistration);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idTenantRegistration
     * @param string $reason
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function declineRegistration(int $idTenantRegistration, string $reason): TenantRegistrationResponseTransfer
    {
        return $this->getFactory()
            ->createRegistrationDecliner()
            ->decline($idTenantRegistration, $reason);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationCollectionTransfer
     */
    public function getTenantRegistrations(TenantRegistrationCriteriaTransfer $criteriaTransfer): TenantRegistrationCollectionTransfer
    {
        return $this->getRepository()->getTenantRegistrations($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer|null
     */
    public function findTenantRegistrationById(int $idTenantRegistration): ?TenantRegistrationTransfer
    {
        return $this->getRepository()->findTenantRegistrationById($idTenantRegistration);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantOnboardingMessageTransfer $messageTransfer
     *
     * @return void
     */
    public function processOnboardingStep(TenantOnboardingMessageTransfer $messageTransfer): void
    {
        $this->getFactory()
            ->createOnboardingProcessor()
            ->process($messageTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $email
     *
     * @return bool
     */
    public function isEmailAvailable(string $email): bool
    {
        return $this->getRepository()->isEmailAvailable($email);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $tenantName
     *
     * @return bool
     */
    public function isTenantNameAvailable(string $tenantName): bool
    {
        return $this->getRepository()->isTenantNameAvailable($tenantName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function createTenant(TenantTransfer $tenantTransfer): TenantTransfer
    {
        return $this->getEntityManager()->createTenant($tenantTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function updateTenant(TenantTransfer $tenantTransfer): TenantTransfer
    {
        return $this->getEntityManager()->updateTenant($tenantTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idTenant
     *
     * @return void
     */
    public function deleteTenant(int $idTenant): void
    {
        $this->getEntityManager()->deleteTenant($idTenant);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\TenantCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantCollectionTransfer
     */
    public function getTenants(TenantCriteriaTransfer $criteriaTransfer): TenantCollectionTransfer
    {
        return $this->getRepository()->getTenants($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idTenant
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantById(int $idTenant): ?TenantTransfer
    {
        return $this->getRepository()->findTenantById($idTenant);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByIdentifier(string $identifier): ?TenantTransfer
    {
        return $this->getRepository()->findTenantByIdentifier($identifier);
    }
}
