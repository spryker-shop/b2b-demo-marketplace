<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage;

use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\ProductCategoryStorage\ProductCategoryStorageDependencyProvider as SprykerProductCategoryStorageDependencyProvider;

/**
 * Backport of spryker/suite#1619 (CC-31649): the event facade for the chunked category fan-out.
 */
class ProductCategoryStorageDependencyProvider extends SprykerProductCategoryStorageDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_EVENT = 'FACADE_EVENT';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addEventFacade($container);

        return $container;
    }

    protected function addEventFacade(Container $container): Container
    {
        $container->set(static::FACADE_EVENT, function (Container $container): EventFacadeInterface {
            return $container->getLocator()->event()->facade();
        });

        return $container;
    }
}
