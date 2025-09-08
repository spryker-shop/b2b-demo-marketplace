<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Go\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface;
use Go\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;

class RegistrationSubmitter implements RegistrationSubmitterInterface
{
    public function __construct(
        protected RegistrationValidatorInterface $registrationValidator,
        protected PasswordValidatorInterface $passwordValidator,
        protected TenantOnboardingEntityManagerInterface $entityManager,
        protected TenantOnboardingRepositoryInterface $repository,
        protected TenantOnboardingConfig $config,
        protected RegistrationAccepterInterface $registrationAccepter,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationResponseTransfer
     */
    public function submit(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationResponseTransfer
    {
        $responseTransfer = new TenantRegistrationResponseTransfer();
        $errors = [];

        // Validate registration data
        $validationErrors = $this->registrationValidator->validate($tenantRegistrationTransfer);
        if (!empty($validationErrors)) {
            $errors = array_merge($errors, $validationErrors);
        }

        // Validate password
        if ($tenantRegistrationTransfer->getPassword()) {
            $passwordErrors = $this->passwordValidator->validate($tenantRegistrationTransfer->getPassword());
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }
        }

        // Check for duplicates
        if (!$this->repository->isEmailAvailable($tenantRegistrationTransfer->getEmail() ?? '')) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('email');
            $errorTransfer->setMessage('Email is already registered');
            $errors[] = $errorTransfer;
        }

        if (!$this->repository->isTenantNameAvailable($tenantRegistrationTransfer->getTenantName() ?? '')) {
            $errorTransfer = new TenantRegistrationErrorTransfer();
            $errorTransfer->setField('tenantName');
            $errorTransfer->setMessage('Tenant name is already taken');
            $errors[] = $errorTransfer;
        }

        if (!empty($errors)) {
            $responseTransfer->setIsSuccessful(false);
            $responseTransfer->setErrors(new \ArrayObject($errors));
            return $responseTransfer;
        }

        // Hash password
        $hashedPassword = password_hash(
            $tenantRegistrationTransfer->getPassword() ?? '',
            PASSWORD_BCRYPT
        );
        $tenantRegistrationTransfer->setPasswordHash($hashedPassword);
        $tenantRegistrationTransfer->setStatus(TenantOnboardingConfig::REGISTRATION_STATUS_PENDING);

        // Save registration
        $savedRegistration = $this->entityManager->createTenantRegistration($tenantRegistrationTransfer);

        if ($this->config->isRequestAutoApproved()) {
            return $this->registrationAccepter->accept($savedRegistration->getIdTenantRegistration());
        }

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($savedRegistration->getIdTenantRegistration());

        return $responseTransfer;
    }
}
