<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Validator;

use Demo\Zed\CmsBlockCustomerGroup\Business\Checker\CmsBlockCustomerGroupCheckerInterface;
use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;

class CmsBlockCustomerValidator implements CmsBlockValidatorInterface
{
    public function __construct(
        protected CmsBlockCustomerGroupCheckerInterface $cmsBlockCustomerGroupChecker,
    ) {
    }

    public function validate(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
        ?CmsBlockValidationResponseTransfer $cmsBlockValidationResponseTransfer = null,
    ): CmsBlockValidationResponseTransfer {
        $cmsBlockValidationResponseTransfer = $cmsBlockValidationResponseTransfer ?? new CmsBlockValidationResponseTransfer();
        $cmsBlockTransfer = $cmsBlockValidationRequestTransfer->getCmsBlockOrFail();
        $customerTransfer = $cmsBlockValidationRequestTransfer->getCustomer();

        if ($customerTransfer === null) {
            return $cmsBlockValidationResponseTransfer->setIsValid(false);
        }

        return $cmsBlockValidationResponseTransfer->setIsValid(
            $this->cmsBlockCustomerGroupChecker->hasCustomerAccessToCmsBlock(
                $cmsBlockTransfer,
                $customerTransfer->getIdCustomerOrFail(),
            ),
        );
    }
}
