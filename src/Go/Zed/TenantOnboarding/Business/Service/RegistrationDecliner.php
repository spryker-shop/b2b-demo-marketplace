<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Go\Zed\TenantOnboarding\Communication\Plugin\Mail\TenantDeclinedMailTypeBuilderPlugin;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;
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
        $mailTransfer = (new MailTransfer())
            ->setType(TenantDeclinedMailTypeBuilderPlugin::MAIL_TYPE)
            ->setTenantRegistration($registrationTransfer);

        $this->mailFacade->handleMail($mailTransfer);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($idTenantRegistration);

        return $responseTransfer;
    }
}
