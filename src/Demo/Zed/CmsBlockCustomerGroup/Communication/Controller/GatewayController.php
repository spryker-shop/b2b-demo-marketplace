<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Communication\Controller;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupFacadeInterface getFacade()
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface getRepository()
 */
class GatewayController extends AbstractGatewayController
{
    public function checkCmsBlockValidityAction(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer {
        return $this->getFacade()->validateAccessToCmsBlock($cmsBlockValidationRequestTransfer);
    }
}
