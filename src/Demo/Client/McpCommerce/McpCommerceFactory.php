<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\McpCommerce;

use Demo\Client\McpCommerce\Zed\McpCommerceZedStub;
use Demo\Client\McpCommerce\Zed\McpCommerceZedStubInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class McpCommerceFactory extends AbstractFactory
{
    public function createMcpCommerceZedStub(): McpCommerceZedStubInterface
    {
        return new McpCommerceZedStub($this->getZedRequestClient());
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(McpCommerceDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
