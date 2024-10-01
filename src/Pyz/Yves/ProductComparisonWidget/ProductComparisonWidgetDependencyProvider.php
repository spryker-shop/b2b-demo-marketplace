<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonWidget;

use Pyz\Client\ProductComparison\ProductComparisonClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class ProductComparisonWidgetDependencyProvider extends AbstractBundleDependencyProvider
{
    public const CLIENT_CUSTOMER = 'CUSTOMER_CLIENT';

    public const CLIENT_PRODUCT_COMPARISON = 'PRODUCT_COMPARISON_CLIENT';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $this->addCustomerClient($container);
        $this->addProductComparisonClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addCustomerClient(Container $container): void
    {
        $container->set(self::CLIENT_CUSTOMER, static function (Container $container): CustomerClientInterface {
            return $container->getLocator()->customer()->client();
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addProductComparisonClient(Container $container): void
    {
        $container->set(self::CLIENT_PRODUCT_COMPARISON, static function (Container $container): ProductComparisonClientInterface {
            return $container->getLocator()->productComparison()->client();
        });
    }
}
