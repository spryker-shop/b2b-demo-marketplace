<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonWidget;

use Pyz\Client\ProductComparison\ProductComparisonClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;

class ProductComparisonWidgetFactory extends AbstractFactory
{
    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonWidgetDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getProductComparisonClient(): ProductComparisonClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonWidgetDependencyProvider::CLIENT_PRODUCT_COMPARISON);
    }
}
