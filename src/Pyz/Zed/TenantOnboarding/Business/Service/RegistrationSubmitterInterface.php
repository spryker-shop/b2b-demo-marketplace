<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;

interface RegistrationSubmitterInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function submit(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationResponseTransfer;
}