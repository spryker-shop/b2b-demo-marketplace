<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication;

use Go\Zed\TenantOnboarding\Communication\Form\TenantRegistrationForm;
use Go\Zed\TenantOnboarding\Communication\Table\TenantRegistrationTable;
use Go\Zed\TenantOnboarding\Communication\Table\TenantTable;
use Go\Zed\TenantOnboarding\TenantOnboardingDependencyProvider;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery;
use Spryker\Zed\Acl\Business\AclFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use Spryker\Zed\User\Business\UserFacadeInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 */
class TenantOnboardingCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Go\Zed\TenantOnboarding\Communication\Table\TenantRegistrationTable
     */
    public function createTenantRegistrationTable(): TenantRegistrationTable
    {
        return new TenantRegistrationTable($this->getFacade());
    }

    /**
     * @return \Go\Zed\TenantOnboarding\Communication\Table\TenantTable
     */
    public function createTenantTable(): TenantTable
    {
        return new TenantTable(PyzTenantQuery::create());
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createTenantRegistrationForm(): FormInterface
    {
        return $this->getFormFactory()->create(TenantRegistrationForm::class);
    }

    public function getUserFacade(): UserFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_USER);
    }

    public function getAclFacade(): AclFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_ACL);
    }

    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_STORE);
    }

    public function getTwigEnvironment(): Environment
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::TWIG_ENVIRONMENT);
    }

    public function getMailFacade(): \Spryker\Zed\Mail\Business\MailFacadeInterface
    {
        return $this->getProvidedDependency(TenantOnboardingDependencyProvider::FACADE_MAIL);
    }

    /**
     * @return \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    public function getLocator()
    {
        return parent::getContainer()->getLocator();
    }
}
