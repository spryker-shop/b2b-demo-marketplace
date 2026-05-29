<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\CmsBlockWidget;

use Demo\Client\CmsBlockCustomerGroup\CmsBlockCustomerGroupClientInterface;
use Demo\Yves\CmsBlockWidget\Validator\CmsBlockValidator;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerShop\Yves\CmsBlockWidget\CmsBlockWidgetFactory as SprykerCmsBlockWidgetFactory;
use SprykerShop\Yves\CmsBlockWidget\Validator\CmsBlockValidatorInterface;

class CmsBlockWidgetFactory extends SprykerCmsBlockWidgetFactory
{
    public function createCmsBlockValidator(): CmsBlockValidatorInterface
    {
        return new CmsBlockValidator(
            $this->getCmsBlockCustomerGroupClient(),
            $this->getCustomerClient(),
        );
    }

    protected function getCmsBlockCustomerGroupClient(): CmsBlockCustomerGroupClientInterface
    {
        return $this->getProvidedDependency(CmsBlockWidgetDependencyProvider::CLIENT_CMS_BLOCK_CUSTOMER_GROUP);
    }

    protected function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(CmsBlockWidgetDependencyProvider::CLIENT_CUSTOMER);
    }
}
