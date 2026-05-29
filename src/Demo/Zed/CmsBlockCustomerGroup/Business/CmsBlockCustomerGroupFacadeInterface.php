<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business;

use Generated\Shared\Transfer\CmsBlockTransfer;
use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;
use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;

interface CmsBlockCustomerGroupFacadeInterface
{
    /**
     * Specification:
     * - Persists customer group assignments for a CMS block by diffing requested vs current rows.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return void
     */
    public function saveCmsBlockCustomerGroups(CmsBlockTransfer $cmsBlockTransfer): void;

    /**
     * Specification:
     * - Returns the customer group collection assigned to the given CMS block.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return \Generated\Shared\Transfer\CustomerGroupCollectionTransfer
     */
    public function getCmsBlockCustomerGroups(CmsBlockTransfer $cmsBlockTransfer): CustomerGroupCollectionTransfer;

    /**
     * Specification:
     * - Checks if the CMS block is visible to the customer based on the configured personalization rules.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\CmsBlockValidationResponseTransfer
     */
    public function validateAccessToCmsBlock(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer;
}
