<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Zed\Event\Business\EventFacadeInterface;

class RegistrationAccepter implements RegistrationAccepterInterface
{
    public function __construct(
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantOnboardingRepositoryInterface $repository,
        protected EventFacadeInterface $eventFacade
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

        $eventName = TenantOnboardingConfig::TENANT_REGISTERED_EVENT;
        if ($registrationTransfer->getDataSet() === 'full') {
            $eventName = TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA;
        }
        $this->eventFacade->trigger($eventName, $queueMessage);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($idTenantRegistration);

        return $responseTransfer;
    }
}
