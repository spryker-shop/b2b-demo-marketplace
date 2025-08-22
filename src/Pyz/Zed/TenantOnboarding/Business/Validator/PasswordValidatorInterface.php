<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Validator;

use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;

interface PasswordValidatorInterface
{
    /**
     * @param string $password
     *
     * @return array<\Generated\Shared\Transfer\TenantRegistrationErrorTransfer>
     */
    public function validate(string $password): array;
}