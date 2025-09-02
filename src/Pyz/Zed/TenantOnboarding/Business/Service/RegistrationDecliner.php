<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;
use Pyz\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Zed\Mail\Business\MailFacadeInterface;

class RegistrationDecliner implements RegistrationDeclinerInterface
{
    public function __construct(
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantOnboardingRepositoryInterface $repository,
        protected MailFacadeInterface $mailFacade
    ) {
    }

    /**
     * @param int $idTenantRegistration
     * @param string $reason
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function decline(int $idTenantRegistration, string $reason): TenantRegistrationResponseTransfer
    {
        $responseTransfer = new TenantRegistrationResponseTransfer();

        $registrationTransfer = $this->repository->findTenantRegistrationById($idTenantRegistration);
        if (!$registrationTransfer) {
            $responseTransfer->setIsSuccessful(false);
            return $responseTransfer;
        }

        // Update status and reason
        $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_DECLINED);
        $registrationTransfer->setDeclineReason($reason);
        $this->entityManager->updateTenantRegistration($registrationTransfer);

        // Send decline notification email
        $mailTransfer = new MailTransfer();
        $mailTransfer->setType('tenant-registration-declined');

        $this->mailFacade->handleMail($mailTransfer);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($idTenantRegistration);

        return $responseTransfer;
    }
}
