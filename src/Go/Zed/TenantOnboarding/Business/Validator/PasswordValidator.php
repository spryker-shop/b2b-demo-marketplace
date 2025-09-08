<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business\Validator;

use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;

class PasswordValidator implements PasswordValidatorInterface
{
    /**
     * @var \Go\Zed\TenantOnboarding\TenantOnboardingConfig
     */
    protected TenantOnboardingConfig $config;

    /**
     * @param \Go\Zed\TenantOnboarding\TenantOnboardingConfig $config
     */
    public function __construct(TenantOnboardingConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $password
     *
     * @return array<\Generated\Shared\Transfer\TenantRegistrationErrorTransfer>
     */
    public function validate(string $password): array
    {
        $errors = [];
        $policy = $this->config->getPasswordPolicy();

        // Check minimum length
        if (strlen($password) < $policy->getMinLength()) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage(sprintf('Password must be at least %d characters long', $policy->getMinLength()));
            $errors[] = $errorTransfer;
        }

        // Check uppercase requirement
        if ($policy->getRequireUpper() && !preg_match('/[A-Z]/', $password)) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage('Password must contain at least one uppercase letter');
            $errors[] = $errorTransfer;
        }

        // Check lowercase requirement
        if ($policy->getRequireLower() && !preg_match('/[a-z]/', $password)) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage('Password must contain at least one lowercase letter');
            $errors[] = $errorTransfer;
        }

        // Check number requirement
        if ($policy->getRequireNumber() && !preg_match('/[0-9]/', $password)) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage('Password must contain at least one number');
            $errors[] = $errorTransfer;
        }

        // Check special character requirement
        if ($policy->getRequireSpecial() && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('password');
            $errorTransfer->setMessage('Password must contain at least one special character');
            $errors[] = $errorTransfer;
        }

        return $errors;
    }
}
