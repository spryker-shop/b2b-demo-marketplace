<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Processor;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Pyz\Zed\TenantOnboarding\Business\TenantOnboardingBusinessFactory;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Pyz\Zed\TenantOnboarding\TenantOnboardingConfig;

class OnboardingProcessor implements OnboardingProcessorInterface
{
    /**
     * @param array<\Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface> $onboardingStepPlugins
     */
    public function __construct(
        protected array $onboardingStepPlugins,
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantBehaviorFacadeInterface $tenantBehaviorFacade,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\TenantOnboardingMessageTransfer $messageTransfer
     *
     * @return void
     */
    public function process(TenantOnboardingMessageTransfer $messageTransfer): void
    {
        $registrationTransfer = $messageTransfer->getTenantRegistration();
        if (!$registrationTransfer) {
            return;
        }
        $currentTenantId = $this->tenantBehaviorFacade->getCurrentTenantId();
        $this->tenantBehaviorFacade->setCurrentTenantId($registrationTransfer->getTenantName());

        // Update status to processing
        $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_PROCESSING);
        $this->entityManager->updateTenantRegistration($registrationTransfer);

        $success = true;
        $errors = [];

        foreach ($this->onboardingStepPlugins as $plugin) {
            $result = $plugin->execute($registrationTransfer);

            if (!$result->getIsSuccessful()) {
                $success = false;
                $errors = array_merge($errors, $result->getErrors());
                break; // Stop on first failure
            }
        }

        // Update final status
        if ($success) {
            $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_COMPLETED);
        } else {
            $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_FAILED);
            // Could log errors or send notification here
        }

        $this->entityManager->updateTenantRegistration($registrationTransfer);
        $this->tenantBehaviorFacade->setCurrentTenantId($currentTenantId);
        dd($registrationTransfer->toArray(),$result->toArray());
    }
}
