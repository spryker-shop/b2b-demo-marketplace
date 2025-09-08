<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business\Processor;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Zed\Company\Business\Exception\InvalidCompanyCreationException;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

class OnboardingProcessor implements OnboardingProcessorInterface
{
    use TransactionTrait;
    /**
     * @param array<\Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface> $onboardingStepPlugins
     */
    public function __construct(
        protected array $onboardingStepPlugins,
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantBehaviorFacadeInterface $tenantBehaviorFacade,
        protected StoreFacadeInterface $storeFacade,
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
        $currentTenantReference = $this->tenantBehaviorFacade->getCurrentTenantReference();
        $this->tenantBehaviorFacade->setCurrentTenantReference($registrationTransfer->getTenantName());
        if (count($this->storeFacade->getAllStores()) === 0) {
            $registrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_FAILED);
            $this->entityManager->updateTenantRegistration($registrationTransfer);
            $this->tenantBehaviorFacade->setCurrentTenantReference($currentTenantReference);

            throw new InvalidCompanyCreationException('No stores found. Tenant Data Import was not processed correctly.');
        }

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
        }

        $this->entityManager->updateTenantRegistration($registrationTransfer);
        $this->tenantBehaviorFacade->setCurrentTenantReference($currentTenantReference);

        if (!$result->getIsSuccessful()) {
            throw new \Exception(implode(', ', $result->getErrors()));
        }
    }
}
