<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Validator;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;

class RegistrationValidator implements RegistrationValidatorInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return array<\Generated\Shared\Transfer\TenantRegistrationErrorTransfer>
     */
    public function validate(TenantRegistrationTransfer $tenantRegistrationTransfer): array
    {
        $errors = [];

        // Validate company name
        if (!$tenantRegistrationTransfer->getCompanyName() || trim($tenantRegistrationTransfer->getCompanyName()) === '') {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('companyName');
            $errorTransfer->setMessage('Company name is required');
            $errors[] = $errorTransfer;
        } elseif (strlen(trim($tenantRegistrationTransfer->getCompanyName())) < 2) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('companyName');
            $errorTransfer->setMessage('Company name must be at least 2 characters long');
            $errors[] = $errorTransfer;
        }

        // Validate tenant name
        if (!$tenantRegistrationTransfer->getTenantName() || trim($tenantRegistrationTransfer->getTenantName()) === '') {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('tenantName');
            $errorTransfer->setMessage('Tenant name is required');
            $errors[] = $errorTransfer;
        } elseif (!preg_match('/^[a-z0-9_-]+$/', $tenantRegistrationTransfer->getTenantName())) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('tenantName');
            $errorTransfer->setMessage('Tenant name can only contain lowercase letters, numbers, underscores and hyphens');
            $errors[] = $errorTransfer;
        } elseif (strlen(trim($tenantRegistrationTransfer->getTenantName())) < 3) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('tenantName');
            $errorTransfer->setMessage('Tenant name must be at least 3 characters long');
            $errors[] = $errorTransfer;
        }

        // Validate email
        if (!$tenantRegistrationTransfer->getEmail() || trim($tenantRegistrationTransfer->getEmail()) === '') {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('email');
            $errorTransfer->setMessage('Email is required');
            $errors[] = $errorTransfer;
        } elseif (!filter_var($tenantRegistrationTransfer->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('email');
            $errorTransfer->setMessage('Invalid email format');
            $errors[] = $errorTransfer;
        }

        // Validate password
        if (!$tenantRegistrationTransfer->getPassword() || trim($tenantRegistrationTransfer->getPassword()) === '') {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage('Password is required');
            $errors[] = $errorTransfer;
        }

        return $errors;
    }
}