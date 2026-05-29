<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup\Zed;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;

interface CmsBlockCustomerGroupStubInterface
{
    public function checkCmsBlockValidity(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer;
}
