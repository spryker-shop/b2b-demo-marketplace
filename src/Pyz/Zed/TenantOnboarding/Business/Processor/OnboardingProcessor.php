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
use Spryker\Zed\Company\Business\Exception\InvalidCompanyCreationException;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;

class OnboardingProcessor implements OnboardingProcessorInterface
{
    use TransactionTrait;
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

        $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_PROCESSING);
        $this->entityManager->updateTenantRegistration($registrationTransfer);

        try {
            $result = $this->getTransactionHandler()->handleTransaction(function () use ($registrationTransfer): TenantOnboardingStepResultTransfer {
                $result = (new TenantOnboardingStepResultTransfer())
                    ->setIsSuccessful(true);
                foreach ($this->onboardingStepPlugins as $plugin) {
                    $result = $plugin->execute($registrationTransfer);

                    if (!$result->getIsSuccessful()) {
                        throw new \Exception(implode(', ', $result->getErrors()));
                    }

                    if ($result->getTenantRegistration()) {
                        $registrationTransfer = $result->getTenantRegistrationOrFail();
                    }
                }

                return $result;
            });
        } catch (\Exception|\Throwable $exception) {
            $result = (new TenantOnboardingStepResultTransfer())
                ->setIsSuccessful(false)
                ->addError($exception->getMessage());
        }

        if ($result->getIsSuccessful()) {
            $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_COMPLETED);
        } else {
            $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_FAILED);
            $registrationTransfer->setErrors(json_encode($result->getErrors()));
        }

        $this->entityManager->updateTenantRegistration($registrationTransfer);
        $this->tenantBehaviorFacade->setCurrentTenantId($currentTenantId);

        if (!$result->getIsSuccessful()) {
            throw new \Exception(implode(', ', $result->getErrors()));
        }
    }
}
