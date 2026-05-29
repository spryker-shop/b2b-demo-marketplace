<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup;

use Demo\Client\CmsBlockCustomerGroup\Zed\CmsBlockCustomerGroupStub;
use Demo\Client\CmsBlockCustomerGroup\Zed\CmsBlockCustomerGroupStubInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class CmsBlockCustomerGroupFactory extends AbstractFactory
{
    public function createZedStub(): CmsBlockCustomerGroupStubInterface
    {
        return new CmsBlockCustomerGroupStub($this->getZedRequestClient());
    }

    protected function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(CmsBlockCustomerGroupDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
