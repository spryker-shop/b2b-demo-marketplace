<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\ProductGrossMargin;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class ProductGrossMarginDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_PRICE_PRODUCT_STORAGE = 'CLIENT_PRICE_PRODUCT_STORAGE';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);

        $container = $this->addPriceProductStorageClient($container);

        return $container;
    }

    public function addPriceProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRICE_PRODUCT_STORAGE, function (Container $container) {
            return $container->getLocator()->priceProductStorage()->client();
        });

        return $container;
    }
}
