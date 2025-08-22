<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding;

use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\CreateBackofficeUserOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\CreateDefaultStoreOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\CreateTenantOnboardingStepPlugin;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding\TenantDataImportOnboardingStepPlugin;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class TenantOnboardingDependencyProvider extends AbstractBundleDependencyProvider
{
    public const CLIENT_QUEUE = 'CLIENT_QUEUE';
    public const CLIENT_MAIL = 'CLIENT_MAIL';
    public const PLUGINS_ONBOARDING_STEP = 'PLUGINS_ONBOARDING_STEP';

    public const FACADE_USER = 'FACADE_USER';
    public const FACADE_ACL = 'FACADE_ACL';
    public const FACADE_STORE = 'FACADE_STORE';

    public const FACADE_TENANT_BEHAVIOR = 'FACADE_TENANT_BEHAVIOR';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addQueueClient($container);
        $container = $this->addMailClient($container);
        $container = $this->addOnboardingStepPlugins($container);
        $container = $this->addTenantBehaviorFacade($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addQueueClient($container);
        $container = $this->addMailClient($container);
        $container = $this->addOnboardingStepPlugins($container);
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
    protected function addQueueClient(Container $container): Container
    {
        $container->set(static::CLIENT_QUEUE, function (Container $container) {
            return $container->getLocator()->queue()->client();
        });

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
    protected function addMailClient(Container $container): Container
    {
        $container->set(static::CLIENT_MAIL, function (Container $container) {
            return $container->getLocator()->mail()->client();
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
            new CreateDefaultStoreOnboardingStepPlugin(),
            new CreateBackofficeUserOnboardingStepPlugin(),
            new TenantDataImportOnboardingStepPlugin(),
        ];
    }
}
