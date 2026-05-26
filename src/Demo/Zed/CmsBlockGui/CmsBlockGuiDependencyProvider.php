<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui;

use Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupFacadeInterface;
use Demo\Zed\CustomerGroup\Business\CustomerGroupFacadeInterface;
use Pyz\Zed\CmsBlockGui\CmsBlockGuiDependencyProvider as PyzCmsBlockGuiDependencyProvider;
use Spryker\Zed\Kernel\Container;

class CmsBlockGuiDependencyProvider extends PyzCmsBlockGuiDependencyProvider
{
    public const string FACADE_CUSTOMER_GROUP = 'FACADE_CUSTOMER_GROUP';

    public const string FACADE_CMS_BLOCK_CUSTOMER_GROUP = 'FACADE_CMS_BLOCK_CUSTOMER_GROUP';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addCustomerGroupFacade($container);
        $container = $this->addCmsBlockCustomerGroupFacade($container);

        return $container;
    }

    protected function addCustomerGroupFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER_GROUP, function (Container $container): CustomerGroupFacadeInterface {
            /** @var \Demo\Zed\CustomerGroup\Business\CustomerGroupFacadeInterface $customerGroupFacade */
            $customerGroupFacade = $container->getLocator()->customerGroup()->facade();

            return $customerGroupFacade;
        });

        return $container;
    }

    protected function addCmsBlockCustomerGroupFacade(Container $container): Container
    {
        $container->set(static::FACADE_CMS_BLOCK_CUSTOMER_GROUP, function (Container $container): CmsBlockCustomerGroupFacadeInterface {
            return $container->getLocator()->cmsBlockCustomerGroup()->facade();
        });

        return $container;
    }
}
