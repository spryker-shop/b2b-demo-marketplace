<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Checker;

use Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface;
use Generated\Shared\Transfer\CmsBlockTransfer;

class CmsBlockCustomerGroupChecker implements CmsBlockCustomerGroupCheckerInterface
{
    public function __construct(
        protected CmsBlockCustomerGroupRepositoryInterface $cmsBlockCustomerGroupRepository,
    ) {
    }

    public function hasCustomerAccessToCmsBlock(CmsBlockTransfer $cmsBlockTransfer, int $idCustomer): bool
    {
        $idCmsBlock = $cmsBlockTransfer->getIdCmsBlockOrFail();

        if (!count($this->cmsBlockCustomerGroupRepository->getCmsBlockCustomerGroupIds($idCmsBlock))) {
            return true;
        }

        return $this->cmsBlockCustomerGroupRepository->hasCustomerInCmsBlockCustomerGroups($idCmsBlock, $idCustomer);
    }
}
