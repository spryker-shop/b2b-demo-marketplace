<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;

interface CmsBlockCustomerGroupClientInterface
{
    /**
     * Specification:
     * - Calls Zed gateway to check if the CMS block is visible for the given customer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\CmsBlockValidationResponseTransfer
     */
    public function checkCmsBlockValidity(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer;
}
