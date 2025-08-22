<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;

interface RegistrationAccepterInterface
{
    /**
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function accept(int $idTenantRegistration): TenantRegistrationResponseTransfer;
}