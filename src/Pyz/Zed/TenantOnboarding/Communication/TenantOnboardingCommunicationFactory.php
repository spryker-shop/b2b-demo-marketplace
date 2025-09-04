<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication;

use Pyz\Zed\TenantOnboarding\Communication\Form\TenantRegistrationForm;
use Pyz\Zed\TenantOnboarding\Communication\Table\TenantRegistrationTable;
use Pyz\Zed\TenantOnboarding\Communication\Table\TenantTable;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery;
use Pyz\Zed\TenantOnboarding\TenantOnboardingDependencyProvider;
use Spryker\Zed\Acl\Business\AclFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\SalesInvoice\Dependency\Facade\SalesInvoiceToMailFacadeInterface;
use Spryker\Zed\SalesInvoice\SalesInvoiceDependencyProvider;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use Spryker\Zed\User\Business\UserFacade;
use Spryker\Zed\User\Business\UserFacadeInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 */
class TenantOnboardingCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Pyz\Zed\TenantOnboarding\Communication\Table\TenantRegistrationTable
     */
    public function createTenantRegistrationTable(): TenantRegistrationTable
    {
        return new TenantRegistrationTable($this->getFacade());
    }

    /**
     * @return \Pyz\Zed\TenantOnboarding\Communication\Table\TenantTable
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
}
