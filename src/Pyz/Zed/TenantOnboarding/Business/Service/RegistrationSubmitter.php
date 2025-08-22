<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Service;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantRegistrationResponseTransfer;
use Generated\Shared\Transfer\TenantRegistrationErrorTransfer;
use Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface;
use Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface;
use Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface;

class RegistrationSubmitter implements RegistrationSubmitterInterface
{
    private const STATUS_PENDING = 'pending';

    /**
     * @var \Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface
     */
    protected RegistrationValidatorInterface $registrationValidator;

    /**
     * @var \Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface
     */
    protected PasswordValidatorInterface $passwordValidator;

    /**
     * @var \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface
     */
    protected TenantOnboardingEntityManagerInterface $entityManager;

    /**
     * @var \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface
     */
    protected TenantOnboardingRepositoryInterface $repository;

    /**
     * @param \Pyz\Zed\TenantOnboarding\Business\Validator\RegistrationValidatorInterface $registrationValidator
     * @param \Pyz\Zed\TenantOnboarding\Business\Validator\PasswordValidatorInterface $passwordValidator
     * @param \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingEntityManagerInterface $entityManager
     * @param \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface $repository
     */
    public function __construct(
        RegistrationValidatorInterface $registrationValidator,
        PasswordValidatorInterface $passwordValidator,
        TenantOnboardingEntityManagerInterface $entityManager,
        TenantOnboardingRepositoryInterface $repository
    ) {
        $this->registrationValidator = $registrationValidator;
        $this->passwordValidator = $passwordValidator;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
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
        $tenantRegistrationTransfer->setStatus(static::STATUS_PENDING);

        // Save registration
        $savedRegistration = $this->entityManager->createTenantRegistration($tenantRegistrationTransfer);

        $responseTransfer->setIsSuccessful(true);
        $responseTransfer->setIdTenantRegistration($savedRegistration->getIdTenantRegistration());

        return $responseTransfer;
    }
}
