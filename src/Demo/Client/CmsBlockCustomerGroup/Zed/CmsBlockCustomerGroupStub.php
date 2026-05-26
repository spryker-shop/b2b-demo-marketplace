<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup\Zed;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class CmsBlockCustomerGroupStub implements CmsBlockCustomerGroupStubInterface
{
    protected const string GATEWAY_URL = '/cms-block-customer-group/gateway/check-cms-block-validity';

    public function __construct(protected ZedRequestClientInterface $zedRequestClient)
    {
    }

    /**
     * @see \Demo\Zed\CmsBlockCustomerGroup\Communication\Controller\GatewayController::checkCmsBlockValidityAction()
     *
     * @param \Generated\Shared\Transfer\CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\CmsBlockValidationResponseTransfer
     */
    public function checkCmsBlockValidity(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
    ): CmsBlockValidationResponseTransfer {
        /** @var \Generated\Shared\Transfer\CmsBlockValidationResponseTransfer $cmsBlockValidationResponseTransfer */
        $cmsBlockValidationResponseTransfer = $this->zedRequestClient->call(
            static::GATEWAY_URL,
            $cmsBlockValidationRequestTransfer,
        );

        return $cmsBlockValidationResponseTransfer;
    }
}
