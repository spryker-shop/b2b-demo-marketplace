<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business;

use Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface;
use Go\Zed\TenantOnboarding\Business\Processor\OnboardingProcessor;
use Go\Zed\TenantOnboarding\Business\Processor\OnboardingProcessorInterface;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationAccepter;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationAccepterInterface;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationDecliner;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationDeclinerInterface;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationSubmitter;
use Go\Zed\TenantOnboarding\Business\Service\RegistrationSubmitterInterface;
use Go\Zed\TenantOnboarding\Business\Validator\PasswordValidator;
use Go\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface;
use Go\Zed\TenantOnboarding\Business\Validator\RegistrationValidator;
use Go\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingDependencyProvider;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Mail\Business\MailFacadeInterface;

/**
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface getEntityManager()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 */
class TenantOnboardingBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Go\Zed\TenantOnboarding\Business\Service\RegistrationSubmitterInterface
     */
    public function createRegistrationSubmitter(): RegistrationSubmitterInterface
    {
        return new RegistrationSubmitter(
            $this->createRegistrationValidator(),
            $this->createPasswordValidator(),
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getConfig(),
            $this->createRegistrationAccepter(),
        );
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Business\Service\RegistrationAccepterInterface
     */
    public function createRegistrationAccepter(): RegistrationAccepterInterface
    {
        return new RegistrationAccepter(
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getEventFacade(),
        );
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Business\Service\RegistrationDeclinerInterface
     */
    public function createRegistrationDecliner(): RegistrationDeclinerInterface
    {
        return new RegistrationDecliner(
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getMailFacade(),
        );
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Business\Processor\OnboardingProcessorInterface
     */
    public function createOnboardingProcessor(): OnboardingProcessorInterface
    {
        return new OnboardingProcessor(
            $this->getOnboardingStepPlugins(),
            $this->getEntityManager(),
            $this->getTenantBehaviorFacade(),
            $this->getContainer()->getLocator()->store()->facade(),
        );
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface
     */
    public function createRegistrationValidator(): RegistrationValidatorInterface
    {
        return new RegistrationValidator();
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface
     */
    public function createPasswordValidator(): PasswordValidatorInterface
    {
        return new PasswordValidator($this->getConfig());
    }

    /**
     * @return array<\Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface>
     */
    public function getOnboardingStepPlugins(): array
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::PLUGINS_ONBOARDING_STEP);
    }

    public function getTenantBehaviorFacade(): TenantBehaviorFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_TENANT_BEHAVIOR);
    }

    protected function getEventFacade(): EventFacadeInterface
    {
        return $this->getContainer()->getLocator()->event()->facade();
    }

    protected function getMailFacade(): MailFacadeInterface
    {
        return $this->getContainer()->getLocator()->mail()->facade();
    }
}
