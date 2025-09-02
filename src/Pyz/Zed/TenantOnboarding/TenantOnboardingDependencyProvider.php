<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding;

use Pyz\Zed\ShopConfiguration\Communication\Plugin\TenantOnboarding\CreateStoreDomainsOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\CreateBackofficeUserOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\CreateTenantOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\EmailNotificationUserOnboardingStepPlugin;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class TenantOnboardingDependencyProvider extends AbstractBundleDependencyProvider
{
    public const FACADE_MAIL = 'FACADE_MAIL';
    public const PLUGINS_ONBOARDING_STEP = 'PLUGINS_ONBOARDING_STEP';

    public const FACADE_USER = 'FACADE_USER';
    public const FACADE_ACL = 'FACADE_ACL';
    public const FACADE_STORE = 'FACADE_STORE';
    public const TWIG_ENVIRONMENT = 'TWIG_ENVIRONMENT';

    /**
     * @uses \Spryker\Zed\Twig\Communication\Plugin\Application\TwigApplicationPlugin::SERVICE_TWIG
     *
     * @var string
     */
    public const SERVICE_TWIG = 'twig';

    public const FACADE_TENANT_BEHAVIOR = 'FACADE_TENANT_BEHAVIOR';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addMailFacade($container);
        $container = $this->addOnboardingStepPlugins($container);
        $container = $this->addTenantBehaviorFacade($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addMailFacade($container);
        $container = $this->addUserFacade($container);
        $container = $this->addAclFacade($container);
        $container = $this->addStoreFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_USER, function (Container $container) {
            return $container->getLocator()->user()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addTenantBehaviorFacade(Container $container): Container
    {
        $container->set(static::FACADE_TENANT_BEHAVIOR, function (Container $container) {
            return $container->getLocator()->tenantBehavior()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addAclFacade(Container $container): Container
    {
        $container->set(static::FACADE_ACL, function (Container $container) {
            return $container->getLocator()->acl()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return $container->getLocator()->store()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addMailFacade(Container $container): Container
    {
        $container->set(static::FACADE_MAIL, function (Container $container) {
            return $container->getLocator()->mail()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addOnboardingStepPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ONBOARDING_STEP, function () {
            return $this->getOnboardingStepPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface>
     */
    protected function getOnboardingStepPlugins(): array
    {
        return [
            new CreateTenantOnboardingStepPlugin(),
            new CreateBackofficeUserOnboardingStepPlugin(),
            new CreateStoreDomainsOnboardingStepPlugin(),
            new EmailNotificationUserOnboardingStepPlugin(),
        ];
    }
}
