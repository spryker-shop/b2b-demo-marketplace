<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\ProductGrossMarginWidget;

use Spryker\Client\Agent\AgentClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;

class ProductGrossMarginWidgetFactory extends AbstractFactory
{
    public function getAgentClient(): AgentClientInterface
    {
        return $this->getProvidedDependency(ProductGrossMarginWidgetDependencyProvider::CLIENT_AGENT);
    }

    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(ProductGrossMarginWidgetDependencyProvider::CLIENT_CUSTOMER);
    }
}
