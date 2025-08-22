<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Validator;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;

interface RegistrationValidatorInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return array<\Generated\Shared\Transfer\TenantRegistrationErrorTransfer>
     */
    public function validate(TenantRegistrationTransfer $tenantRegistrationTransfer): array;
}