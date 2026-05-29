<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\CmsBlockWidget\Validator;

use Demo\Client\CmsBlockCustomerGroup\CmsBlockCustomerGroupClientInterface;
use Generated\Shared\Transfer\CmsBlockTransfer;
use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerShop\Yves\CmsBlockWidget\Validator\CmsBlockValidator as SprykerCmsBlockValidator;

class CmsBlockValidator extends SprykerCmsBlockValidator
{
    public function __construct(
        protected CmsBlockCustomerGroupClientInterface $cmsBlockCustomerGroupClient,
        protected CustomerClientInterface $customerClient,
    ) {
    }

    public function isValid(CmsBlockTransfer $cmsBlockTransfer): bool
    {
        return parent::isValid($cmsBlockTransfer)
            && $this->isAvailableForCustomerGroup($cmsBlockTransfer);
    }

    protected function isAvailableForCustomerGroup(CmsBlockTransfer $cmsBlockTransfer): bool
    {
        if (!$cmsBlockTransfer->getIsRestricted()) {
            return true;
        }

        $customerTransfer = $this->customerClient->getCustomer();
        if ($customerTransfer === null) {
            return false;
        }

        $request = (new CmsBlockValidationRequestTransfer())
            ->setCmsBlock($cmsBlockTransfer)
            ->setCustomer($customerTransfer);

        return $this->cmsBlockCustomerGroupClient
            ->checkCmsBlockValidity($request)
            ->getIsValid() === true;
    }
}
