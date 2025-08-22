<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business;

use Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Pyz\Zed\TenantOnboarding\Business\Processor\OnboardingProcessor;
use Pyz\Zed\TenantOnboarding\Business\Processor\OnboardingProcessorInterface;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationAccepter;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationAccepterInterface;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationDecliner;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationDeclinerInterface;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationSubmitter;
use Pyz\Zed\TenantOnboarding\Business\Service\RegistrationSubmitterInterface;
use Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidator;
use Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface;
use Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidator;
use Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface;
use Pyz\Zed\TenantOnboarding\TenantOnboardingDependencyProvider;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface getEntityManager()
 * @method \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 */
class TenantOnboardingBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Service\RegistrationSubmitterInterface
     */
    public function createRegistrationSubmitter(): RegistrationSubmitterInterface
    {
        return new RegistrationSubmitter(
            $this->createRegistrationValidator(),
            $this->createPasswordValidator(),
            $this->getEntityManager(),
            $this->getRepository(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Service\RegistrationAccepterInterface
     */
    public function createRegistrationAccepter(): RegistrationAccepterInterface
    {
        return new RegistrationAccepter(
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getQueueClient(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Service\RegistrationDeclinerInterface
     */
    public function createRegistrationDecliner(): RegistrationDeclinerInterface
    {
        return new RegistrationDecliner(
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getMailClient(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Processor\OnboardingProcessorInterface
     */
    public function createOnboardingProcessor(): OnboardingProcessorInterface
    {
        return new OnboardingProcessor(
            $this->getOnboardingStepPlugins(),
            $this->getEntityManager(),
            $this->getTenantBehaviorFacade(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface
     */
    public function createRegistrationValidator(): RegistrationValidatorInterface
    {
        return new RegistrationValidator();
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface
     */
    public function createPasswordValidator(): PasswordValidatorInterface
    {
        return new PasswordValidator($this->getConfig());
    }

    /**
     * @return \Spryker\Client\Queue\QueueClientInterface
     */
    public function getQueueClient()
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::CLIENT_QUEUE);
    }

    /**
     * @return \Spryker\Client\Mail\MailClientInterface
     */
    public function getMailClient()
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::CLIENT_MAIL);
    }

    /**
     * @return array<\Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface>
     */
    public function getOnboardingStepPlugins(): array
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::PLUGINS_ONBOARDING_STEP);
    }

    public function getTenantBehaviorFacade(): TenantBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_TENANT_BEHAVIOR);
    }
}
