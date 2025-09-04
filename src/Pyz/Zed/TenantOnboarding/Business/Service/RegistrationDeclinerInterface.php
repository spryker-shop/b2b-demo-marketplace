<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;

interface RegistrationDeclinerInterface
{
    /**
     * @param int $idTenantRegistration
     * @param string $reason
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function decline(int $idTenantRegistration, string $reason): TenantRegistrationResponseTransfer;
}