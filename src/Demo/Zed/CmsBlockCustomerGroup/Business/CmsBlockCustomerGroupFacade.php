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
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupBusinessFactory getFactory()
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface getRepository()
 */
class CmsBlockCustomerGroupFacade extends AbstractFacade implements CmsBlockCustomerGroupFacadeInterface
{
    public function saveCmsBlockCustomerGroups(CmsBlockTransfer $cmsBlockTransfer): void
    {
        $this->getFactory()
            ->createCmsBlockCustomerGroupWriter()
            ->saveCmsBlockCustomerGroups($cmsBlockTransfer);
    }

    public function getCmsBlockCustomerGroups(CmsBlockTransfer $cmsBlockTransfer): CustomerGroupCollectionTransfer
    {
        return $this->getRepository()
            ->getCmsBlockCustomerGroups((int)$cmsBlockTransfer->getIdCmsBlockOrFail());
    }

    public function validateAccessToCmsBlock(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer {
        return $this->getFactory()
            ->createCmsBlockValidator()
            ->validate($cmsBlockValidationRequestTransfer);
    }
}
