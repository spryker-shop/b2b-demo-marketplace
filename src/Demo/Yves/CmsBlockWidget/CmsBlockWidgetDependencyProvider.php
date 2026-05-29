<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\CmsBlockWidget;

use Pyz\Yves\CmsBlockWidget\CmsBlockWidgetDependencyProvider as PyzCmsBlockWidgetDependencyProvider;
use Spryker\Yves\Kernel\Container;

class CmsBlockWidgetDependencyProvider extends PyzCmsBlockWidgetDependencyProvider
{
    public const string CLIENT_CMS_BLOCK_CUSTOMER_GROUP = 'CLIENT_CMS_BLOCK_CUSTOMER_GROUP';

    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addCmsBlockCustomerGroupClient($container);
        $container = $this->addCustomerClient($container);

        return $container;
    }

    protected function addCmsBlockCustomerGroupClient(Container $container): Container
    {
        $container->set(static::CLIENT_CMS_BLOCK_CUSTOMER_GROUP, function (Container $container) {
            return $container->getLocator()->cmsBlockCustomerGroup()->client();
        });

        return $container;
    }

    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }
}
