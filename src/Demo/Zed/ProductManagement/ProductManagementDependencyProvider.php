<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductManagement;

use Demo\Zed\MoneyGui\Communication\Plugin\Form\MoneyFormTypePlugin;
use Pyz\Zed\ProductManagement\ProductManagementDependencyProvider as PyzProductManagementDependencyProvider;
use Spryker\Zed\Kernel\Communication\Form\FormTypeInterface;
use Spryker\Zed\Kernel\Container;

class ProductManagementDependencyProvider extends PyzProductManagementDependencyProvider
{
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container->set(static::FACADE_PRICE, function (Container $container) {
            return $container->getLocator()->price()->facade();
        });

        return $container;
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    protected function createMoneyFormTypePlugin(Container $container): FormTypeInterface
    {
        return new MoneyFormTypePlugin();
    }
}
