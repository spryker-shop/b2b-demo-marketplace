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
use Spryker\Client\Mail\MailClientInterface;

class RegistrationDecliner implements RegistrationDeclinerInterface
{
    /**
     * @var \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface
     */
    protected TenantOnboardingEntityManagerInterface $entityManager;

    /**
     * @var \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface
     */
    protected TenantOnboardingRepositoryInterface $repository;

    /**
     * @var \Spryker\Client\Mail\MailClientInterface
     */
    protected MailClientInterface $mailClient;

    /**
     * @param \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface $entityManager
     * @param \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface $repository
     * @param \Spryker\Client\Mail\MailClientInterface $mailClient
     */
    public function __construct(
        TenantOnboardingEntityManagerInterface $entityManager,
        TenantOnboardingRepositoryInterface $repository,
        MailClientInterface $mailClient
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->mailClient = $mailClient;
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
        $mailTransfer->setLocale('en_US');
        $mailTransfer->addRecipient($registrationTransfer->getEmail(), $registrationTransfer->getCompanyName());
        $mailTransfer->setData([
            'companyName' => $registrationTransfer->getCompanyName(),
            'tenantName' => $registrationTransfer->getTenantName(),
            'declineReason' => $reason,
        ]);

        $this->mailClient->sendMail($mailTransfer);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($idTenantRegistration);

        return $responseTransfer;
    }
}