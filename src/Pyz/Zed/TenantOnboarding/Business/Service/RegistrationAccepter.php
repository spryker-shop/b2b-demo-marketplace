<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;
use Pyz\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Client\Queue\QueueClientInterface;

class RegistrationAccepter implements RegistrationAccepterInterface
{
    public function __construct(
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantOnboardingRepositoryInterface $repository,
        protected QueueClientInterface $queueClient
    ) {
    }

    /**
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function accept(int $idTenantRegistration): TenantRegistrationResponseTransfer
    {
        $responseTransfer = new TenantRegistrationResponseTransfer();

        $registrationTransfer = $this->repository->findTenantRegistrationById($idTenantRegistration);
        if (!$registrationTransfer) {
            $responseTransfer->setIsSuccessful(false);
            return $responseTransfer;
        }

        // Update status to approved
        $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_APPROVED);
        $this->entityManager->updateTenantRegistration($registrationTransfer);

        // Queue onboarding process
        $messageTransfer = new TenantOnboardingMessageTransfer();
        $messageTransfer->setIdTenantRegistration($idTenantRegistration);
        $messageTransfer->setTenantRegistration($registrationTransfer);

        $queueMessage = new QueueSendMessageTransfer();
        $queueMessage->setBody(json_encode($messageTransfer->toArray()));

        $this->queueClient->sendMessage(TenantOnboardingConfig::QUEUE_NAME_TENANT_ONBOARDING, $queueMessage);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($idTenantRegistration);

        return $responseTransfer;
    }
}
