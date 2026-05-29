<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Demo\Client\CmsBlockCustomerGroup\CmsBlockCustomerGroupFactory getFactory()
 */
class CmsBlockCustomerGroupClient extends AbstractClient implements CmsBlockCustomerGroupClientInterface
{
    public function checkCmsBlockValidity(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer {
        return $this->getFactory()
            ->createZedStub()
            ->checkCmsBlockValidity($cmsBlockValidationRequestTransfer);
    }
}
