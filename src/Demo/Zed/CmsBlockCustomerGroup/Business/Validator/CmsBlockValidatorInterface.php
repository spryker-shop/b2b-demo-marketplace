<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Validator;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;

interface CmsBlockValidatorInterface
{
    public function validate(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
        ?CmsBlockValidationResponseTransfer $cmsBlockValidationResponseTransfer = null,
    ): CmsBlockValidationResponseTransfer;
}
